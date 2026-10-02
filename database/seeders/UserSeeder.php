<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create System Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@email.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        // 2. Create Sample Customer User
        $customerUser = User::firstOrCreate(
            ['email' => 'customer@email.com'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );
        $customerUser->assignRole('customer');

        // 3. Create Customer Profile
        Customer::firstOrCreate(
            ['user_id' => $customerUser->id],
            [
                'shipping_address' => 'Dhaka, Bangladesh',
                'billing_address' => 'Dhaka, Bangladesh',
                'city' => 'Dhaka',
            ]
        );
    }
}