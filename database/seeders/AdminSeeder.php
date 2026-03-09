<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $walletService = app(WalletService::class);

        $admin = User::create([
            'name' => 'Movam Admin',
            'email' => 'admin@movam.com',
            'password' => Hash::make('password'), // Change in production
        ]);

        $admin->assignRole('admin');
        $walletService->createWallet($admin);
    }
}
