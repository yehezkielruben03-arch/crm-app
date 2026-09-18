<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Notification;
use Illuminate\Console\Command;

/**
 * Gap #11 — Sistem Reminder Follow-Up Aktif
 *
 * Logika (Bahasa Bayi):
 * Sales kadang lupa untuk follow-up customer. Command ini seperti
 * "alarm pengingat" yang jalan setiap pagi. Dia mengecek customer
 * mana yang status-nya Prospect/Lead dan sudah lebih dari 7 hari
 * tidak ada transaksi baru, lalu kirim notifikasi ke Sales yang
 * menangani customer tersebut.
 */
class SendFollowUpReminders extends Command
{
    protected $signature   = 'customer:send-followup-reminders';
    protected $description = 'Kirim notifikasi reminder ke Sales untuk customer yang perlu di-follow-up';

    // Hari tanpa aktivitas sebelum dikirim reminder
    private const FOLLOWUP_THRESHOLD_DAYS = 7;

    public function handle(): int
    {
        $threshold = now()->subDays(self::FOLLOWUP_THRESHOLD_DAYS);

        // Cari customer Prospect/Lead yang dibuat lebih dari 7 hari lalu
        // dan belum ada RFQ sama sekali (berarti belum pernah diproses)
        $customers = Customer::whereIn('status', ['Prospect', 'Lead', 'Pending'])
            ->where('created_at', '<=', $threshold)
            ->whereDoesntHave('rfqs') // Belum ada RFQ = belum di-follow-up
            ->whereNotNull('sales_id')
            ->with('sales')
            ->get();

        if ($customers->isEmpty()) {
            $this->info('Tidak ada customer yang perlu diingatkan hari ini.');
            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($customers as $customer) {
            // Cek: jangan kirim notif duplikat jika sudah dikirim hari ini
            $alreadySentToday = \App\Models\Notification::where('user_id', $customer->sales_id)
                ->where('type', 'followup_reminder')
                ->where('message', 'LIKE', "%{$customer->company_name}%")
                ->whereDate('created_at', today())
                ->exists();

            if ($alreadySentToday) {
                continue;
            }

            Notification::send(
                userId:  $customer->sales_id,
                type:    'followup_reminder',
                title:   'Reminder: Follow Up Customer',
                message: "Jangan lupa follow up \"{$customer->company_name}\" yang sudah " . self::FOLLOWUP_THRESHOLD_DAYS . " hari belum diproses. Segera buat RFQ!",
                link:    route('customers.show', $customer)
            );

            $sent++;
        }

        $this->info("{$sent} reminder follow-up berhasil dikirim ke Sales.");
        return self::SUCCESS;
    }
}
