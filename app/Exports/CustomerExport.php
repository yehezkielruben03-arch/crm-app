<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        $user = auth()->user();
        $query = Customer::with('sales');

        if (!$user->isAdminOrAbove()) {
            $query->where('sales_id', $user->id);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function headings(): array
    {
        return [
            'Kode Perusahaan',
            'Nama Perusahaan',
            'Brand',
            'Tipe Customer',
            'Industri',
            'Skala',
            'Kategori Area',
            'Kawasan',
            'Alamat',
            'Kota',
            'Provinsi',
            'Kode Pos',
            'Negara',
            'Telp',
            'Telepon Kantor',
            'WhatsApp',
            'Preferred Contact',
            'Divisi',
            'Email',
            'Website',
            'NPWP',
            'NIB',
            'Status',
            'Sales',
            'CP Nama',
            'CP Jabatan',
            'CP Email',
            'CP Telp',
            'Catatan',
        ];
    }

    public function map($customer): array
    {
        $primary = $customer->primaryContact();
        
        return [
            $customer->company_code,
            $customer->company_name,
            $customer->brand_name,
            $customer->customer_type,
            $customer->industry,
            $customer->company_scale,
            $customer->area_category,
            $customer->region,
            $customer->address,
            $customer->city,
            $customer->province,
            $customer->postal_code,
            $customer->country,
            $customer->phone,
            $primary?->office_phone ?? '-',
            $primary?->whatsapp ?? '-',
            $primary?->preferred_contact ?? '-',
            $primary?->division ?? '-',
            $customer->email,
            $customer->website,
            $customer->npwp,
            $customer->nib,
            $customer->status,
            $customer->sales?->name,
            $primary?->name ?? $customer->cp_name,
            $primary?->position ?? $customer->cp_position,
            $primary?->email ?? $customer->cp_email,
            $primary?->phone ?? $customer->cp_phone,
            $customer->notes,
        ];
    }
}
