<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
    }

    public function test_customer_can_place_order()
    {
        $walletService = app(WalletService::class);

        // 1. Create Merchant
        $merchantUser = User::create([
            'name' => 'Merchant User',
            'email' => 'merchant@example.com',
            'password' => Hash::make('password'),
        ]);
        $merchantUser->assignRole('merchant');
        $merchant = Merchant::create([
            'user_id' => $merchantUser->id,
            'business_name' => 'Test Merchant',
            'address' => 'Merchant Address',
            'phone' => '1234567890',
        ]);
        $walletService->createWallet($merchantUser);

        // 2. Create Category and Product
        $category = Category::create([
            'merchant_id' => $merchant->id,
            'name' => 'Test Category',
        ]);
        $product = Product::create([
            'merchant_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Test Product',
            'price' => 1000.00,
        ]);

        // 3. Create Customer and give balance
        $customerUser = User::create([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
        ]);
        $customerUser->assignRole('customer');
        $walletService->createWallet($customerUser);
        $walletService->deposit($customerUser, 5000.00);

        // 4. Place Order
        $response = $this->actingAs($customerUser)
            ->postJson('/api/orders', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2]
                ],
                'delivery_address' => 'Customer Address',
                'delivery_lat' => 6.5244,
                'delivery_lng' => 3.3792,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('orders', [
            'customer_id' => $customerUser->id,
            'total_amount' => 2000.00,
            'status' => 'pending',
        ]);

        // Check customer wallet balance (5000 - 2000 - 1000 delivery fee = 2000)
        $this->assertEquals(2000.00, $customerUser->wallet->fresh()->balance);
    }

    public function test_funds_distributed_on_delivery()
    {
        $walletService = app(WalletService::class);

        // 1. Setup Merchant, Product, Customer
        $merchantUser = User::create(['name' => 'M', 'email' => 'm@e.com', 'password' => Hash::make('p')]);
        $merchantUser->assignRole('merchant');
        $merchant = Merchant::create(['user_id' => $merchantUser->id, 'business_name' => 'M', 'address' => 'Merchant Address', 'commission_rate' => 12]);
        $walletService->createWallet($merchantUser);

        $category = Category::create(['merchant_id' => $merchant->id, 'name' => 'C']);
        $product = Product::create(['merchant_id' => $merchant->id, 'category_id' => $category->id, 'name' => 'P', 'price' => 10000]);

        $customerUser = User::create(['name' => 'C', 'email' => 'c@e.com', 'password' => Hash::make('p')]);
        $customerUser->assignRole('customer');
        $walletService->createWallet($customerUser);
        $walletService->deposit($customerUser, 20000);

        // 2. Place Order (10,000 + 1,000 delivery fee = 11,000)
        $response = $this->actingAs($customerUser)->postJson('/api/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'delivery_address' => 'A', 'delivery_lat' => 0, 'delivery_lng' => 0,
        ]);
        if ($response->status() !== 201) {
            dump($response->json());
        }
        $response->assertStatus(201);
        $orderId = $response->json('id');

        // 3. Assign Rider and Deliver
        $riderUser = User::create(['name' => 'R', 'email' => 'r@e.com', 'password' => Hash::make('p')]);
        $riderUser->assignRole('rider');
        $walletService->createWallet($riderUser);

        $this->actingAs($merchantUser)->patchJson("/api/orders/{$orderId}/status", ['status' => 'preparing']);
        $this->actingAs($riderUser)->patchJson("/api/orders/{$orderId}/status", ['status' => 'accepted']);
        $res = $this->actingAs($riderUser)->patchJson("/api/orders/{$orderId}/status", ['status' => 'delivered']);
        if ($res->status() !== 200) {
            dump($res->json());
        }

        $order = Order::find($orderId);
        dump([
            'order_status' => $order->status,
            'rider_id' => $order->rider_id,
            'merchant_wallet' => $merchantUser->wallet->fresh()->balance,
            'rider_wallet' => $riderUser->wallet->fresh()->balance,
        ]);

        // 4. Verify Balances
        // Merchant: 10,000 - 1,200 (12%) = 8,800
        $this->assertEquals(8800.00, $merchantUser->wallet->fresh()->balance);
        
        // Rider: 1,000 * 0.7 = 700
        $this->assertEquals(700.00, $riderUser->wallet->fresh()->balance);

        // Admin: 1,200 + 300 = 1,500
        $adminUser = User::role('admin')->first();
        $this->assertEquals(1500.00, $adminUser->wallet->fresh()->balance);
    }
}
