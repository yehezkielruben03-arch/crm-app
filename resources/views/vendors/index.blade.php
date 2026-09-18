<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manajemen Vendor (Subcon)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-bold mb-4">Tambah Vendor Baru</h3>
                    <form action="{{ route('vendors.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Vendor <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_vendor" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">PIC / Penanggung Jawab</label>
                            <input type="text" name="pic" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kontak (No HP / Telp)</label>
                            <input type="text" name="kontak" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">NPWP</label>
                            <input type="text" name="npwp" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Alamat Lengkap</label>
                            <textarea name="alamat" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"></textarea>
                        </div>
                        <div class="md:col-span-2 text-right">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow">
                                Simpan Vendor
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-bold mb-4">Daftar Vendor</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-no-wrap w-full whitespace-no-wrap table-auto">
                            <thead>
                                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                    <th class="px-4 py-3">Nama Vendor</th>
                                    <th class="px-4 py-3">PIC & Kontak</th>
                                    <th class="px-4 py-3">Alamat</th>
                                    <th class="px-4 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y">
                                @forelse($vendors as $vendor)
                                <tr class="text-gray-700">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold">{{ $vendor->nama_vendor }}</p>
                                        <p class="text-xs text-gray-500">NPWP: {{ $vendor->npwp ?? '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <p>{{ $vendor->pic ?? '-' }}</p>
                                        <p class="text-xs text-gray-500">{{ $vendor->kontak ?? '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm">{{ $vendor->alamat ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <form action="{{ route('vendors.destroy', $vendor->id) }}" method="POST" onsubmit="return confirm('Hapus vendor ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 font-semibold text-xs">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-center text-sm text-gray-500">Belum ada data vendor.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
