<?php

namespace App\Console\Commands;

use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Console\Command;

class InitSalesChatRooms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:init-sales-rooms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inisialisasi saluran obrolan (Teams style) untuk seluruh user Sales aktif';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $salesUsers = User::whereIn('role', ['Sales', 'Sales Marketing'])
            ->where('status', 'Active')
            ->get();

        if ($salesUsers->isEmpty()) {
            $this->info('Tidak ditemukan user Sales aktif.');
            return 0;
        }

        $created = 0;
        foreach ($salesUsers as $sales) {
            $room = ChatRoom::getOrCreateForSales($sales);
            if ($room->wasRecentlyCreated) {
                $created++;
                $this->line("Dibuat saluran baru untuk Sales: {$sales->name}");
            }
        }

        $this->info("Inisialisasi selesai. {$created} saluran baru dibuat dari total {$salesUsers->count()} sales aktif.");
        return 0;
    }
}
