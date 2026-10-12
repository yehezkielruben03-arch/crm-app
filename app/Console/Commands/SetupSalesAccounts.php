<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SetupSalesAccounts extends Command
{
    protected $signature = 'crm:setup-sales';
    protected $description = 'Setup atau update akun sales Bu Ade, Pak Yudi, dan Sales Baru serta tampilkan daftar user aktif';

    public function handle()
    {
        $this->info('========================================================');
        $this->info('DAFTAR AKUN USER SEBELUM PROSES UPDATE:');
        $this->info('========================================================');
        $this->displayUsersTable();

        $this->info("\nMemproses pembaruan akun sales...");

        // 1. Akun Bu Ade
        $userAde = User::where('email', 'ade-zulvida@pedia-group.jp')
            ->orWhere('email', 'ade@crm.com')
            ->orWhere('username', 'ade_zulvida')
            ->first();

        if ($userAde) {
            $userAde->update([
                'name'     => $userAde->name ?: 'Ade Zulvida',
                'username' => $userAde->username ?: 'ade_zulvida',
                'email'    => 'ade-zulvida@pedia-group.jp',
                'password' => Hash::make('Pedia551%'),
                'role'     => 'Sales',
                'status'   => 'Active',
            ]);
            $this->info('✓ Akun Bu Ade berhasil diperbarui (ID: ' . $userAde->id . ')');
        } else {
            $userAde = User::create([
                'name'     => 'Ade Zulvida',
                'username' => 'ade_zulvida',
                'email'    => 'ade-zulvida@pedia-group.jp',
                'password' => Hash::make('Pedia551%'),
                'role'     => 'Sales',
                'status'   => 'Active',
            ]);
            $this->info('+ Akun Bu Ade berhasil dibuat (ID: ' . $userAde->id . ')');
        }

        // 2. Akun Pak Yudi
        $userYudi = User::where('email', 'sales-pti1@pedia-group.jp')
            ->orWhere('email', 'yudi@crm.com')
            ->orWhere('username', 'Yudi')
            ->orWhere('username', 'yudi')
            ->first();

        if ($userYudi) {
            $userYudi->update([
                'name'     => $userYudi->name ?: 'Yudi Siswanto',
                'username' => $userYudi->username ?: 'Yudi',
                'email'    => 'sales-pti1@pedia-group.jp',
                'password' => Hash::make('Pedia551%'),
                'role'     => 'Sales',
                'status'   => 'Active',
            ]);
            $this->info('✓ Akun Pak Yudi berhasil diperbarui (ID: ' . $userYudi->id . ')');
        } else {
            $userYudi = User::create([
                'name'     => 'Yudi Siswanto',
                'username' => 'Yudi',
                'email'    => 'sales-pti1@pedia-group.jp',
                'password' => Hash::make('Pedia551%'),
                'role'     => 'Sales',
                'status'   => 'Active',
            ]);
            $this->info('+ Akun Pak Yudi berhasil dibuat (ID: ' . $userYudi->id . ')');
        }

        // 3. Akun Sales Baru (Konversi dari Akun Sales 2)
        $userSalesBaru = User::where('email', 'sales-pti2@pedia-group.jp')
            ->orWhere('email', 'sales2@crm.com')
            ->orWhere('username', 'sales_2')
            ->orWhere('username', 'sales_baru')
            ->first();

        if ($userSalesBaru) {
            $userSalesBaru->update([
                'name'     => 'Sales Baru',
                'username' => 'sales_2',
                'email'    => 'sales-pti2@pedia-group.jp',
                'password' => Hash::make('Pedia551%'),
                'role'     => 'Sales',
                'status'   => 'Active',
            ]);
            $this->info('✓ Akun Sales Baru berhasil diperbarui dari akun ID ' . $userSalesBaru->id);
        } else {
            $userSalesBaru = User::create([
                'name'     => 'Sales Baru',
                'username' => 'sales_2',
                'email'    => 'sales-pti2@pedia-group.jp',
                'password' => Hash::make('Pedia551%'),
                'role'     => 'Sales',
                'status'   => 'Active',
            ]);
            $this->info('+ Akun Sales Baru berhasil dibuat (ID: ' . $userSalesBaru->id . ')');
        }

        $this->info('');
        $this->info('========================================================');
        $this->info('DAFTAR AKUN USER TERKINI SETELAH PROSES SELESAI:');
        $this->info('========================================================');
        $this->displayUsersTable();

        return 0;
    }

    private function displayUsersTable()
    {
        $users = User::all(['id', 'name', 'username', 'email', 'role', 'status'])
            ->map(function ($u) {
                return [
                    'ID'       => $u->id,
                    'Name'     => $u->name,
                    'Username' => $u->username,
                    'Email'    => $u->email,
                    'Role'     => $u->role,
                    'Status'   => $u->status,
                ];
            });

        $this->table(['ID', 'Name', 'Username', 'Email', 'Role', 'Status'], $users);
    }
}
