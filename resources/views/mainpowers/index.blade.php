<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Portal Mainpower (Internal Pedia)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-bold mb-2">Setup Biaya Mainpower Standar</h3>
                    <p class="text-sm text-gray-600 mb-6">Nilai ini akan dijadikan acuan otomatis saat Admin Purchase menggunakan fitur <strong>"Gunakan MP"</strong> pada blok Jasa Pemasangan.</p>
                    
                    <form action="{{ route('mainpowers.store') }}" method="POST">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gaji Pokok / MP (Rp)</label>
                                <input type="number" name="mp" value="{{ old('mp', (int)($mainpower->mp ?? 0)) }}" required min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 calc-trigger">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tunjangan Hari Raya / THR (Rp)</label>
                                <input type="number" name="thr" value="{{ old('thr', (int)($mainpower->thr ?? 0)) }}" required min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 calc-trigger">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">BPJS Kesehatan (Rp)</label>
                                <input type="number" name="bpjs_kes" value="{{ old('bpjs_kes', (int)($mainpower->bpjs_kes ?? 0)) }}" required min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 calc-trigger">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">BPJS Ketenagakerjaan (Rp)</label>
                                <input type="number" name="bpjs_tk" value="{{ old('bpjs_tk', (int)($mainpower->bpjs_tk ?? 0)) }}" required min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 calc-trigger">
                            </div>
                        </div>

                        <div class="bg-blue-50 p-4 rounded-lg flex justify-between items-center mb-6 border border-blue-100">
                            <div>
                                <h4 class="text-sm font-bold text-blue-800">Total Biaya Standar per Hari</h4>
                                <p class="text-xs text-blue-600">Angka ini yang akan masuk ke kolom HPP Jasa secara otomatis.</p>
                            </div>
                            <div class="text-2xl font-bold text-blue-700">
                                Rp <span id="total-preview">{{ number_format($mainpower->total ?? 0, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded shadow-lg transition">
                                Simpan / Perbarui Data MP
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.calc-trigger');
            const totalPreview = document.getElementById('total-preview');

            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    let total = 0;
                    inputs.forEach(inp => {
                        total += (parseFloat(inp.value) || 0);
                    });
                    totalPreview.innerText = new Intl.NumberFormat('id-ID').format(total);
                });
            });
        });
    </script>
</x-app-layout>
