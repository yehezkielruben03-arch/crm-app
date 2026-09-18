<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Ruben (Super Admin)',
                'username' => 'ruben',
                'email'    => 'ruben@crm.com',
                'password' => Hash::make('password123'),
                'role'     => 'Super Admin',
                'phone'    => '081200000001',
                'status'   => 'Active',
            ],
            [
                'name'     => 'Admin Purchase',
                'username' => 'admin_purchase',
                'email'    => 'admin@crm.com',
                'password' => Hash::make('password123'),
                'role'     => 'Admin',
                'phone'    => '081200000002',
                'status'   => 'Active',
            ],
            [
                'name'     => 'Laras (Leader)',
                'username' => 'laras',
                'email'    => 'laras@crm.com',
                'password' => Hash::make('password123'),
                'role'     => 'Leader',
                'phone'    => '081200000003',
                'status'   => 'Active',
            ],
            [
                'name'     => 'Ade Zulvida',
                'username' => 'ade_zulvida',
                'email'    => 'ade@crm.com',
                'password' => Hash::make('password123'),
                'role'     => 'Sales',
                'phone'    => '081200000004',
                'status'   => 'Active',
                'monthly_target' => 500000000,
            ],
            [
                'name'     => 'Sales 1',
                'username' => 'sales_1',
                'email'    => 'sales1@crm.com',
                'password' => Hash::make('password123'),
                'role'     => 'Sales',
                'phone'    => '081200000005',
                'status'   => 'Active',
                'monthly_target' => 300000000,
            ],
            [
                'name'     => 'Sales 2',
                'username' => 'sales_2',
                'email'    => 'sales2@crm.com',
                'password' => Hash::make('password123'),
                'role'     => 'Sales',
                'phone'    => '081200000006',
                'status'   => 'Active',
                'monthly_target' => 200000000,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }

        $this->command->info('✅ Users seeded successfully!');
    }
}
