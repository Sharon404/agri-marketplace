<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test farmer
        \App\Models\User::create([
            'name' => 'John Farmer',
            'email' => 'farmer@test.com',
            'password' => bcrypt('password123'),
            'phone' => '0712345678',
            'role' => 'farmer',
            'county' => 'Nairobi',
            'sub_county' => 'Westlands',
        ]);

        // Create test buyer
        \App\Models\User::create([
            'name' => 'Jane Buyer',
            'email' => 'buyer@test.com',
            'password' => bcrypt('password123'),
            'phone' => '0723456789',
            'role' => 'buyer',
            'county' => 'Kiambu',
            'sub_county' => 'Kiambaa',
        ]);

        echo "Test users created successfully!\n";
        echo "Farmer: farmer@test.com / password123\n";
        echo "Buyer: buyer@test.com / password123\n";
    }
}
