<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\Order;
use App\Models\Rider;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Laravel\Sanctum\Sanctum;

class GapAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create roles
        Role::create(['name' => 'customer', 'guard_name' => 'web']);
        Role::create(['name' => 'merchant', 'guard_name' => 'web']);
        Role::create(['name' => 'rider', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
    }

    /**
     * Test Mobile App Config Endpoint (Gap 5)
     */
    public function test_mobile_app_config_endpoint()
    {
        $response = $this->getJson('/api/config');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'android' => ['min_version', 'latest_version', 'force_update', 'store_url'],
                'ios' => ['min_version', 'latest_version', 'force_update', 'store_url'],
                'maintenance_mode',
                'support' => ['phone', 'email', 'whatsapp'],
                'features' => ['referrals_enabled', 'wallet_enabled'],
            ]);
    }

    /**
     * Test Address Management (Gap 3)
     */
    public function test_address_management()
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        // 1. Add Address
        $response = $this->postJson('/api/addresses', [
            'label' => 'Home',
            'address' => '123 Test Street, Lagos',
            'lat' => 6.5244,
            'lng' => 3.3792,
            'is_default' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'label' => 'Home',
                'address' => '123 Test Street, Lagos',
                'is_default' => true,
            ]);

        // 2. List Addresses
        $response = $this->getJson('/api/addresses');
        $response->assertStatus(200)
            ->assertJsonCount(1);
        
        $addressId = $response->json()[0]['id'];

        // 3. Show Address (Verify new logic)
        $response = $this->getJson("/api/addresses/{$addressId}");
        $response->assertStatus(200)
            ->assertJson(['label' => 'Home']);
    }

    /**
     * Test Referral System (Gap 2)
     */
    public function test_referral_system()
    {
        // 1. User A (Referrer)
        $referrer = User::factory()->create(['referral_code' => 'REF123']);
        
        // 2. User B (Referee) registers with code
        // Mock OTP verification (bypass for test or simulate)
        // Since register endpoints are complex with OTP, let's test the logic directly or via model
        
        $user = User::factory()->create(['referred_by' => $referrer->id]);
        
        // Create the Referral record manually as factories bypass controller logic
        \App\Models\Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $user->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($referrer);

        // 3. Check Referrals Endpoint
        $response = $this->getJson('/api/referrals');
        
        $response->assertStatus(200)
            ->assertJsonStructure(['referral_code', 'total_referrals', 'referrals', 'total_earnings'])
            ->assertJson(['total_referrals' => 1]);
    }

    /**
     * Test Ratings & Reviews (Gap 1)
     */
    public function test_ratings_and_reviews()
    {
        // Setup: Customer, Merchant, Order
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        $merchantUser = User::factory()->create();
        $merchantUser->assignRole('merchant');
        $merchant = Merchant::create([
            'user_id' => $merchantUser->id,
            'business_name' => 'Test Burger',
            'address' => 'Lagos',
            'phone' => '08000000000',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'merchant_id' => $merchant->id,
            'order_number' => 'ORD-TEST-1',
            'total_amount' => 5000,
            'delivery_fee' => 500,
            'commission_amount' => 500,
            'rider_share' => 350,
            'status' => 'delivered', // Must be delivered
            'payment_status' => 'paid',
            'payment_method' => 'wallet',
            'delivery_address' => 'Home',
            'delivery_lat' => 0,
            'delivery_lng' => 0,
            'pickup_address' => 'Shop',
            'pickup_lat' => 0,
            'pickup_lng' => 0,
        ]);

        Sanctum::actingAs($customer);

        // 1. Post Review
        $response = $this->postJson('/api/reviews', [
            'order_id' => $order->id,
            'type' => 'merchant',
            'rating' => 5,
            'comment' => 'Great food!',
        ]);

        $response->assertStatus(201);

        // 2. Verify Review Exists
        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'reviewable_id' => $merchant->id,
            'rating' => 5,
        ]);

        // 3. Verify Public Endpoint
        $response = $this->getJson("/api/reviews/merchant/{$merchant->id}");
        $response->assertStatus(200)
            ->assertJsonFragment(['comment' => 'Great food!']);
    }

    /**
     * Test Notification Center (Gap 4)
     */
    public function test_notification_center()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Simulate Notification
        $user->notify(new \App\Notifications\GenericNotification('Welcome', 'Hello World'));

        // 1. Get Notifications
        $response = $this->getJson('/api/notifications');
        
        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Welcome']);

        // 2. Mark All Read
        $response = $this->postJson('/api/notifications/mark-all-read');
        $response->assertStatus(200);
        
        $this->assertEquals(0, $user->unreadNotifications()->count());
    }
}
