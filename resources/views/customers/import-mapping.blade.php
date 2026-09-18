<x-app-layout>
<div class="max-w-4xl mx-auto py-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800 leading-tight flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            Mapping Kolom Excel
        </h2>
        <a href="{{ route('customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700 transition-colors flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Batal & Kembali
        </a>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50/50">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Verifikasi Kolom Excel</h3>
            <p class="text-sm text-gray-600">
                Sistem menemukan beberapa kolom di file Excel yang tidak termasuk dalam kolom standar (seperti Nama Perusahaan, Alamat, dll). 
                Kemungkinan besar ini adalah kolom ceklis untuk penugasan Sales. Silakan petakan kolom-kolom ini ke akun pengguna (Sales) yang tepat.
            </p>
        </div>

        <div class="p-6">
            <form action="{{ route('customers.import.process') }}" method="POST" id="mappingForm">
                @csrf
                <input type="hidden" name="file_path" value="{{ $path }}">
                <input type="hidden" name="header_row" value="{{ $headerRowIndex ?? 1 }}">

                @if(count($unknownHeaders) > 0)
                    <div class="space-y-4">
                        <div class="grid grid-cols-12 gap-4 pb-2 border-b border-gray-100 text-sm font-medium text-gray-500 uppercase tracking-wider">
                            <div class="col-span-5">Kolom di Excel</div>
                            <div class="col-span-2 text-center"></div>
                            <div class="col-span-5">Pilih Akun CRM</div>
                        </div>

                        @foreach($unknownHeaders as $header)
                            <div class="grid grid-cols-12 gap-4 items-center p-3 rounded-xl hover:bg-gray-50 transition-colors border border-transparent hover:border-gray-100">
                                <div class="col-span-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-semibold text-sm uppercase">
                                            {{ substr($header, 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800">{{ $header }}</p>
                                            <p class="text-xs text-gray-500">Akan membaca ceklis (v)</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-span-2 flex justify-center text-gray-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                    </svg>
                                </div>
                                <div class="col-span-5">
                                    <select name="mapping[{{ $header }}]" class="w-full rounded-xl border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500 shadow-sm" style="border: 1px solid #e2e8f0; padding: 0.5rem 1rem;">
                                        <option value="">-- Abaikan (Jangan Di-assign) --</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ (stripos($user->name, $header) !== false) ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->role }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-500 mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800">Tidak ada kolom ekstra yang perlu di-map</h3>
                        <p class="text-sm text-gray-500 mt-1">Sistem hanya mendeteksi kolom standar. Anda bisa langsung melanjutkan import.</p>
                    </div>
                @endif

                <div class="mt-8 flex items-center justify-end gap-3 pt-6 border-t border-gray-100">
                    <a href="{{ route('customers.index') }}" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-gray-200">
                        Batalkan
                    </a>
                    <button type="submit" id="submitBtn" class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-sm focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Proses Import Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('mappingForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Sedang Mengimport...
        `;
    });
</script>
@endpush
</x-app-layout>
