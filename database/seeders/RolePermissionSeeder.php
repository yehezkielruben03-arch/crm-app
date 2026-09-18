<?php

namespace Database\Seeders;

use App\Models\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan data lama
        DB::table('role_permissions')->truncate();

        // Sama persis dengan data di setup_db.php (PHP native)
        $permissions = [
            'Super Admin'     => ['CRUD', 'Approval', 'Report Access', 'Customer Access', 'Region Access'],
            'Admin Purchase'  => ['CRUD', 'Approval', 'Report Access', 'Customer Access', 'Region Access'],
            'Leader'          => ['Approval', 'Report Access', 'Customer Access'],
            'Sales Marketing' => ['Customer Access'],
        ];

        foreach ($permissions as $role => $perms) {
            foreach ($perms as $perm) {
                RolePermission::create([
                    'role_name'       => $role,
                    'permission_name' => $perm,
                ]);
            }
        }

        $this->command->info('✅ Role Permissions seeded successfully!');
    }
}
