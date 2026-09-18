<x-app-layout>

    <!-- Page Header -->
    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between animate-in" style="animation-delay: 0.05s;">
        <div>
            <h1 class="text-xl font-bold flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-violet);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                User Management
            </h1>
            <p class="mt-1 text-sm" style="color: var(--text-muted);">Kelola akun pengguna dan hak akses mereka</p>
        </div>
        @if(auth()->user()->isAdminOrAbove())
        <a href="{{ route('users.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5 hover:shadow-md"
           style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 12px rgba(37,99,235,0.30);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah User
        </a>
        @endif
    </div>

    <!-- Filter Bar -->
    <div class="card filter-bar p-4 mb-5">
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama, email, atau username..."
                    class="w-full pl-9 pr-4 py-2 text-sm rounded-xl outline-none transition-all"
                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                    onfocus="this.style.borderColor='var(--accent-blue)'"
                    onblur="this.style.borderColor='var(--border-color)'">
            </div>
            <x-custom-select name="role" :value="request('role')" placeholder="Semua Role" :options="[
                '' => 'Semua Role',
                'Super Admin' => 'Super Admin',
                'Admin' => 'Admin',
                'Sales Marketing' => 'Sales Marketing',
            ]" />
            <button type="submit"
                class="px-4 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5"
                style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8);">
                Filter
            </button>
            @if(request()->hasAny(['search', 'role']))
            <a href="{{ route('users.index') }}"
               class="px-4 py-2 text-sm text-center rounded-xl transition-colors"
               style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary);">
                Reset
            </a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="card animate-in" style="animation-delay: 0.15s;">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Target (Rp)</th>
                        @if(auth()->user()->isAdminOrAbove())
                        <th>Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white shadow-sm flex-shrink-0"
                                     style="background: linear-gradient(135deg, #818cf8, #a78bfa);">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <p class="font-medium" style="color: var(--text-primary);">{{ $user->name }}</p>
                            </div>
                        </td>
                        <td>
                            <code class="text-xs px-2 py-1 rounded" style="background: var(--bg-secondary); color: var(--text-secondary);">
                                {{ $user->username ?? '-' }}
                            </code>
                        </td>
                        <td style="color: var(--text-secondary);">{{ $user->email }}</td>
                        <td>
                            @php
                                $roleBadge = match($user->role) {
                                    'Super Admin' => 'pending',
                                    'Admin'       => 'new',
                                    'Sales Marketing' => 'approved',
                                    default       => 'pending',
                                };
                            @endphp
                            <span class="status-badge {{ $roleBadge }}">
                                <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                                {{ $user->role_display_name }}
                            </span>
                        </td>
                        <td>
                            @if($user->trashed())
                            <span class="status-badge rejected">
                                <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                                Nonaktif
                            </span>
                            @else
                            <span class="status-badge {{ $user->status === 'Active' ? 'approved' : 'pending' }}">
                                <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                                {{ $user->status }}
                            </span>
                            @endif
                        </td>
                        <td>
                            <code class="text-xs px-2 py-1 rounded" style="background: var(--bg-secondary); color: var(--text-secondary);">
                                {{ $user->monthly_target ? number_format($user->monthly_target, 0, ',', '.') : '-' }}
                            </code>
                        </td>
                        @if(auth()->user()->isAdminOrAbove())
                        <td>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('users.edit', $user) }}" title="Edit"
                                    class="p-1.5 rounded-lg transition-colors inline-block"
                                    style="color: var(--text-muted);"
                                    onmouseenter="this.style.background='rgba(217,119,6,0.08)'; this.style.color='var(--accent-amber)'"
                                    onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @if($user->id !== auth()->id())
                                @if($user->trashed())
                                <form action="{{ route('users.restore', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Aktifkan kembali akun {{ $user->name }}?')">
                                    @csrf
                                    <button type="submit" title="Aktifkan Kembali"
                                        class="p-1.5 rounded-lg transition-colors"
                                        style="color: var(--text-muted);"
                                        onmouseenter="this.style.background='rgba(22,163,74,0.08)'; this.style.color='var(--accent-green)'"
                                        onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    </button>
                                </form>
                                @else
                                <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Nonaktifkan akun {{ $user->name }}? Data terkait (customer, PO, RFQ) akan tetap tersimpan.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Nonaktifkan"
                                        class="p-1.5 rounded-lg transition-colors"
                                        style="color: var(--text-muted);"
                                        onmouseenter="this.style.background='rgba(225,29,72,0.08)'; this.style.color='var(--accent-rose)'"
                                        onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                                @endif
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isAdminOrAbove() ? 7 : 6 }}" class="px-6 py-16 text-center">
                            <p class="font-medium" style="color: var(--text-muted);">Tidak ada user ditemukan</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
        <div class="px-6 py-4" style="border-top: 1px solid var(--border-color);">
            {{ $users->links() }}
        </div>
        @endif
    </div>

</x-app-layout>
