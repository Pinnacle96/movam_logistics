<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Merchant;
use App\Models\Rider;
use App\Models\Category;
use App\Models\Product;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Create Roles
        $roles = ['admin', 'merchant', 'rider', 'customer'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // 0.1 Create Default Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@movam.com'],
            ['name' => 'Movam Admin', 'password' => Hash::make('password')]
        );
        $admin->assignRole('admin');
        Wallet::updateOrCreate(['user_id' => $admin->id], ['balance' => 0]);

        // 1. Merchants with their own Categories & Products
        $merchantsData = [
            [
                'name' => 'Burger King Lagos',
                'email' => 'bk@movam.com',
                'business' => 'Burger King',
                'address' => 'Plot 10, Admiralty Way, Lekki Phase 1',
                'lat' => 6.4490,
                'lng' => 3.4730,
                'categories' => ['Burgers', 'Sides', 'Drinks'],
                'products' => [
                    ['name' => 'Whopper Meal', 'price' => 4500, 'cat' => 'Burgers', 'desc' => 'Classic flame-grilled beef burger with fries and soda.'],
                    ['name' => 'Chicken Royale', 'price' => 3800, 'cat' => 'Burgers', 'desc' => 'Tender chicken breast with lettuce and mayo.'],
                    ['name' => 'French Fries', 'price' => 1200, 'cat' => 'Sides', 'desc' => 'Crispy golden fries.'],
                ]
            ],
            [
                'name' => 'Domino\'s Pizza VI',
                'email' => 'dominos@movam.com',
                'business' => 'Domino\'s Pizza',
                'address' => 'Akin Adesola St, Victoria Island',
                'lat' => 6.4300,
                'lng' => 3.4150,
                'categories' => ['Pizza', 'Chicken', 'Sides'],
                'products' => [
                    ['name' => 'Pepperoni Feast', 'price' => 6500, 'cat' => 'Pizza', 'desc' => 'Classic pepperoni with extra mozzarella.'],
                    ['name' => 'BBQ Chicken Pizza', 'price' => 7200, 'cat' => 'Pizza', 'desc' => 'Grilled chicken, onions, and spicy BBQ sauce.'],
                    ['name' => 'Chicken Wings (6pcs)', 'price' => 3500, 'cat' => 'Chicken', 'desc' => 'Spicy roasted wings.'],
                ]
            ],
            [
                'name' => 'Mama Cass',
                'email' => 'mamacass@movam.com',
                'business' => 'Mama Cass',
                'address' => 'Allen Avenue, Ikeja',
                'lat' => 6.5967,
                'lng' => 3.3421,
                'categories' => ['Main Course', 'Soups', 'Swallow'],
                'products' => [
                    ['name' => 'Jollof Rice & Chicken', 'price' => 3200, 'cat' => 'Main Course', 'desc' => 'Authentic Nigerian smoky jollof with grilled chicken.'],
                    ['name' => 'Egusi Soup', 'price' => 2500, 'cat' => 'Soups', 'desc' => 'Rich melon seed soup with choice of protein.'],
                    ['name' => 'Pounded Yam', 'price' => 1500, 'cat' => 'Swallow', 'desc' => 'Freshly pounded yam.'],
                ]
            ]
        ];

        foreach ($merchantsData as $m) {
            $user = User::updateOrCreate(
                ['email' => $m['email']],
                ['name' => $m['name'], 'password' => Hash::make('password')]
            );
            $user->assignRole('merchant');

            $merchant = Merchant::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'business_name' => $m['business'], 
                    'address' => $m['address'], 
                    'lat' => $m['lat'],
                    'lng' => $m['lng'],
                    'phone' => '08012345678', 
                    'is_active' => true, 
                    'is_verified' => true
                ]
            );

            Wallet::updateOrCreate(['user_id' => $user->id], ['balance' => rand(10000, 50000)]);

            // Create Merchant-Specific Categories
            $merchantCats = [];
            foreach ($m['categories'] as $catName) {
                $merchantCats[$catName] = Category::create([
                    'merchant_id' => $merchant->id,
                    'name' => $catName,
                    'image' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=200'
                ]);
            }

            foreach ($m['products'] as $p) {
                Product::create([
                    'merchant_id' => $merchant->id,
                    'category_id' => $merchantCats[$p['cat']]->id,
                    'name' => $p['name'],
                    'description' => $p['desc'],
                    'price' => $p['price'],
                    'stock' => rand(50, 200),
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600'
                ]);
            }
        }

        // 2. Riders
        $riders = [
            ['name' => 'Sunday Rider', 'email' => 'sunday@movam.com', 'vehicle' => 'Bajaj Pulsar', 'number' => 'LAG-123-ABC', 'lat' => 6.4500, 'lng' => 3.4600],
            ['name' => 'Abiola Fast', 'email' => 'abiola@movam.com', 'vehicle' => 'TVS HLX', 'number' => 'IJK-456-XYZ', 'lat' => 6.6000, 'lng' => 3.3500],
        ];

        foreach ($riders as $r) {
            $user = User::updateOrCreate(
                ['email' => $r['email']],
                ['name' => $r['name'], 'password' => Hash::make('password')]
            );
            $user->assignRole('rider');

            Rider::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'vehicle_type' => 'Motorcycle', 
                    'vehicle_number' => $r['number'], 
                    'license_number' => 'LIC-' . rand(1000, 9999), 
                    'is_active' => true, 
                    'is_verified' => true,
                    'current_lat' => $r['lat'],
                    'current_lng' => $r['lng']
                ]
            );

            Wallet::updateOrCreate(['user_id' => $user->id], ['balance' => rand(5000, 15000)]);
        }

        // 3. Sample Customers
        $customers = [
            ['name' => 'John Customer', 'email' => 'john@movam.com'],
            ['name' => 'Jane Buyer', 'email' => 'jane@movam.com'],
        ];

        foreach ($customers as $c) {
            $user = User::updateOrCreate(
                ['email' => $c['email']],
                ['name' => $c['name'], 'password' => Hash::make('password')]
            );
            $user->assignRole('customer');
            Wallet::updateOrCreate(['user_id' => $user->id], ['balance' => 20000]);
        }
    }
}
