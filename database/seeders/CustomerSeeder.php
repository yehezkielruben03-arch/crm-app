<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $ade = User::where('name', 'Ade Zulvida')->first();
        $sales1 = User::where('name', 'Sales 1')->first();
        $sales2 = User::where('name', 'Sales 2')->first();

        $customers = [
            [
                'company_name'  => 'PT. Karya Bangsa Tbk',
                'industry'      => 'Otomotif',
                'company_scale' => 'Enterprise',
                'area_category' => 'Cikarang Pusat',
                'region'        => 'Delta Silicon',
                'address'       => 'Jl. Delta Silicon II Blok C5 No. 12, Cikarang Pusat, Kabupaten Bekasi, Jawa Barat 17530',
                'phone'         => '021-89990001',
                'email'         => 'corp@karyabangsa.com',
                'website'       => 'https://karyabangsa.com',
                'npwp'          => '01.234.567.8-901.000',
                'status'        => Customer::STATUS_ACTIVE,
                'sales_id'      => $ade->id ?? 1,
                'cp_name'       => 'Budi Santoso',
                'cp_position'   => 'Purchasing Manager',
                'cp_email'      => 'budi@karyabangsa.com',
                'cp_phone'      => '0812-8000-0001',
            ],
            [
                'company_name'  => 'CV. Sehat Selalu',
                'industry'      => 'Logistik',
                'company_scale' => 'Medium',
                'area_category' => 'Cikarang Selatan',
                'region'        => 'EJIP',
                'address'       => 'Jl. EJIP Plot 5A No. 3, Cikarang Selatan, Kabupaten Bekasi, Jawa Barat 17550',
                'phone'         => '021-89990002',
                'email'         => 'info@sehatselalu.com',
                'website'       => 'https://sehatselalu.com',
                'npwp'          => '02.345.678.9-012.000',
                'status'        => Customer::STATUS_ACTIVE,
                'sales_id'      => $sales1->id ?? 1,
                'cp_name'       => 'Siti Aminah',
                'cp_position'   => 'Procurement',
                'cp_email'      => 'siti@sehatselalu.com',
                'cp_phone'      => '0812-8000-0002',
            ],
            [
                'company_name'  => 'Toko Makmur Sentosa',
                'industry'      => 'Elektronik',
                'company_scale' => 'SME',
                'area_category' => 'Cikarang Barat',
                'region'        => 'MM2100',
                'address'       => 'Ruko MM2100 Blok A1 No. 5, Cikarang Barat, Kabupaten Bekasi, Jawa Barat 17520',
                'phone'         => '021-89990003',
                'email'         => 'makmur@tokomakmur.com',
                'website'       => 'https://tokomakmur.com',
                'npwp'          => '03.456.789.0-123.000',
                'status'        => Customer::STATUS_ACTIVE,
                'sales_id'      => $sales2->id ?? 1,
                'cp_name'       => 'Ahmad Rizki',
                'cp_position'   => 'Owner',
                'cp_email'      => 'ahmad@tokomakmur.com',
                'cp_phone'      => '0812-8000-0003',
            ],
            [
                'company_name'  => 'PT. Inaktif Dummy',
                'industry'      => 'Manufacture',
                'company_scale' => 'SME',
                'area_category' => 'Jakarta Timur',
                'region'        => 'Pulogadung',
                'address'       => 'JIEP Pulogadung',
                'phone'         => '021-89990004',
                'email'         => 'inaktif@dummy.com',
                'website'       => 'https://inaktifdummy.com',
                'npwp'          => '04.456.789.0-123.000',
                'status'        => Customer::STATUS_ACTIVE,
                'sales_id'      => $ade->id ?? 1,
                'cp_name'       => 'Joko Inaktif',
                'cp_position'   => 'Purchasing',
                'cp_email'      => 'joko@dummy.com',
                'cp_phone'      => '0812-8000-0004',
            ]
        ];

        foreach ($customers as $c) {
            $code = Customer::generateCompanyCode($c['region'], $c['company_name']);
            $c['company_code'] = $code;
            Customer::updateOrCreate(
                ['company_name' => $c['company_name']],
                $c
            );
        }

        $this->command->info('✅ Customers seeded successfully!');
    }
}
