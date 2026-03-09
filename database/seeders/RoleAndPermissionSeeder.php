<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'manage users',
            'manage merchants',
            'manage riders',
            'manage orders',
            'manage wallet',
            'manage settings',
            'view analytics',
            'place order',
            'track order',
            'accept order',
            'prepare order',
            'deliver order',
            'manage catalog',
            'view earnings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create Roles and Assign Permissions
        
        // Super Admin
        $superAdmin = Role::create(['name' => 'admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Merchant
        $merchant = Role::create(['name' => 'merchant']);
        $merchant->givePermissionTo([
            'manage orders',
            'prepare order',
            'manage catalog',
            'view earnings',
        ]);

        // Rider
        $rider = Role::create(['name' => 'rider']);
        $rider->givePermissionTo([
            'accept order',
            'deliver order',
            'view earnings',
        ]);

        // Customer
        $customer = Role::create(['name' => 'customer']);
        $customer->givePermissionTo([
            'place order',
            'track order',
        ]);
    }
}
