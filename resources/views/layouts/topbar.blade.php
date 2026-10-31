<!-- Fixed Topbar -->
<header class="topbar" id="topbar">
    <div class="flex items-center justify-between w-full">

        <!-- Left: Page Title -->
        <div class="flex items-center gap-3">

            <!-- Mobile burger button -->
            <button
                class="mobile-menu-btn"
                onclick="toggleMobileSidebar()"
                aria-label="Open sidebar menu"
                id="mobileMenuBtn"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div>
                <h1 class="text-xl font-bold leading-tight" style="color: var(--text-primary);">Dashboard</h1>
                <p class="text-sm leading-none mt-0.5" style="color: var(--text-muted);">Welcome back, {{ auth()->user()->name }}!</p>
            </div>
        </div>

        <!-- Right: Search + Actions -->
        <div class="flex items-center gap-3">

            <!-- Search Box -->
            <div class="topbar-search hidden md:flex" role="search">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    id="globalSearch"
                    placeholder="Search anything..."
                    aria-label="Global search"
                />
            </div>

            <!-- Notification Button & Dropdown (Alpine: notificationSystem di app.blade.php) -->
            <div class="relative" x-data="notificationSystem()" x-init="initPolling()" @click.away="open = false">
                <button
                    class="topbar-icon-btn"
                    @click="open = !open"
                    aria-label="Toggle notifications"
                    id="notificationToggleBtn"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span class="notification-badge" x-show="unreadCount > 0" x-text="unreadCount" style="display: none;"></span>
                </button>

                <!-- Notification Dropdown Panel -->
                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-80 rounded-xl shadow-lg bg-white border flex flex-col"
                     style="border-color: var(--border-color); box-shadow: var(--shadow-md); z-index: 100; max-height: 400px; display: none;">
                    
                    <div class="p-3 border-b flex items-center justify-between" style="border-color: var(--border-color); background: var(--bg-secondary); border-radius: 12px 12px 0 0;">
                        <span class="font-bold text-sm" style="color: var(--text-primary);">Notifikasi</span>
                        <template x-if="unreadCount > 0">
                            <button @click="markAllRead" class="text-xs font-semibold hover:underline" style="color: var(--accent-blue);">Tandai Semua Dibaca</button>
                        </template>
                    </div>

                    <div class="flex-1 overflow-y-auto p-2 space-y-1">
                        <template x-if="latestNotifications.length === 0">
                            <div class="text-center py-6 text-xs" style="color: var(--text-muted);">Tidak ada notifikasi baru</div>
                        </template>
                        <template x-for="notif in latestNotifications" :key="notif.id">
                            <a :href="notif.link || '#'" @click.prevent="openNotification(notif.id, notif.link)" class="block p-3 rounded-lg transition-colors hover:bg-gray-50 border border-transparent hover:border-gray-100 cursor-pointer">
                                <p class="text-xs font-bold mb-1" style="color: var(--text-primary);" x-text="notif.title"></p>
                                <p class="text-[11px] leading-snug mb-1.5" style="color: var(--text-secondary);" x-text="notif.message"></p>
                                <p class="text-[9px] font-medium" style="color: var(--accent-blue);" x-text="notif.created_at"></p>
                            </a>
                        </template>
                    </div>

                    <div class="p-2 border-t text-center" style="border-color: var(--border-color);">
                        <a href="{{ route('notifications.index') }}" class="text-xs font-bold hover:underline" style="color: var(--text-primary);">Lihat Semua Notifikasi</a>
                    </div>
                </div>

                <!-- Toast Popup Notifikasi: muncul dari ATAS layar (Top-Right), durasi 4 detik -->
                <div class="fixed top-4 right-4 flex flex-col gap-3" style="pointer-events: none; z-index: 9999;">
                    <template x-for="toast in toasts" :key="toast.id">
                        <div x-show="toast.visible"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="transform -translate-y-10 opacity-0"
                             x-transition:enter-end="transform translate-y-0 opacity-100"
                             x-transition:leave="transition ease-in duration-300"
                             x-transition:leave-start="transform translate-y-0 opacity-100"
                             x-transition:leave-end="transform -translate-y-10 opacity-0"
                             class="bg-white rounded-xl shadow-lg border p-4 flex items-start gap-3 pointer-events-auto"
                             style="border-color: var(--border-color); width: 340px; box-shadow: 0 20px 40px rgba(15,23,42,0.18);">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-bold mb-0.5" style="color: var(--text-primary);" x-text="toast.title"></p>
                                <p class="text-xs" style="color: var(--text-secondary);" x-text="toast.message"></p>
                            </div>
                            <button @click="removeToast(toast.id)" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            @php
                $activePreset = session('filter_preset', 'this_month');
                $activeLabel  = session('filter_label', now()->format('M 1 – M t, Y'));
                $filterStart  = session('filter_start_date', now()->startOfMonth()->toDateString());
                $filterEnd    = session('filter_end_date', now()->endOfMonth()->toDateString());
                $isFiltered   = ($activePreset !== 'this_month');
            @endphp

            <!-- Interactive Global Date Range Filter Popover -->
            <div class="relative" x-data="{
                open: false,
                startDate: {{ json_encode($filterStart) }},
                endDate: {{ json_encode($filterEnd) }},
                preset: {{ json_encode($activePreset) }},
                applyPreset(p) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('preset', p);
                    url.searchParams.delete('start_date');
                    url.searchParams.delete('end_date');
                    url.searchParams.delete('reset_date');
                    url.searchParams.delete('page');
                    window.location.href = url.toString();
                },
                applyCustom() {
                    if (!this.startDate || !this.endDate) return;
                    const url = new URL(window.location.href);
                    url.searchParams.set('start_date', this.startDate);
                    url.searchParams.set('end_date', this.endDate);
                    url.searchParams.delete('preset');
                    url.searchParams.delete('reset_date');
                    url.searchParams.delete('page');
                    window.location.href = url.toString();
                },
                resetFilter() {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('start_date');
                    url.searchParams.delete('end_date');
                    url.searchParams.delete('preset');
                    url.searchParams.set('reset_date', '1');
                    url.searchParams.delete('page');
                    window.location.href = url.toString();
                }
            }" @click.outside="open = false">
                <button
                    type="button"
                    @click="open = !open"
                    class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all duration-200 border shadow-sm group"
                    :class="open ? 'ring-2 ring-blue-500/30' : ''"
                    style="background: {{ $isFiltered ? 'rgba(37,99,235,0.08)' : 'var(--bg-secondary)' }}; 
                           border-color: {{ $isFiltered ? 'rgba(37,99,235,0.35)' : 'var(--border-color)' }}; 
                           color: {{ $isFiltered ? 'var(--accent-blue)' : 'var(--text-secondary)' }};"
                    title="Klik untuk filter periode tanggal sistem"
                >
                    <svg class="w-4 h-4 flex-shrink-0 transition-colors"
                         style="color: {{ $isFiltered ? 'var(--accent-blue)' : 'var(--text-muted)' }};"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span id="currentDate" class="hidden md:inline truncate max-w-[210px]">{{ $activeLabel }}</span>
                    @if($isFiltered)
                        <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0 animate-pulse" title="Filter tanggal sedang aktif"></span>
                    @endif
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 ml-0.5 hidden sm:block"
                         :class="open ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Popover Dropdown Panel -->
                <div
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                    class="absolute right-0 mt-2 w-84 max-w-[92vw] rounded-2xl shadow-xl z-50 p-4 border bg-white"
                    style="border-color: var(--border-color); box-shadow: 0 15px 35px -5px rgba(15,23,42,0.12), 0 5px 15px -3px rgba(15,23,42,0.06);"
                >
                    <!-- Header Popover -->
                    <div class="flex items-center justify-between pb-3 mb-3 border-b" style="border-color: var(--border-color);">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full" style="background: var(--accent-blue);"></span>
                            <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--text-primary); font-family: 'Space Grotesk', sans-serif;">Rentang Periode</span>
                        </div>
                        @if($isFiltered)
                            <button
                                type="button"
                                @click="resetFilter()"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500 hover:text-rose-600 transition-colors"
                                title="Reset filter ke bulan berjalan"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span>Reset Default</span>
                            </button>
                        @endif
                    </div>

                    <!-- Presets 1-Klik -->
                    <div class="space-y-1 mb-3.5">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1.5 px-1">Preset Cepat</p>
                        
                        <!-- Senin - Jumat (Requested!) -->
                        <button
                            type="button"
                            @click="applyPreset('this_workweek')"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-all border"
                            :class="preset === 'this_workweek' ? 'bg-blue-50 text-blue-700 font-semibold border-blue-200 shadow-xs' : 'text-slate-700 hover:bg-slate-50 border-transparent'"
                        >
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span>Minggu Ini (Senin – Jumat)</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] px-1.5 py-0.5 rounded-md font-medium" :class="preset === 'this_workweek' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-600'">Hari Kerja</span>
                                <template x-if="preset === 'this_workweek'">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </div>
                        </button>

                        <!-- Minggu Ini (Full: Sen - Min) -->
                        <button
                            type="button"
                            @click="applyPreset('this_week')"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-all border"
                            :class="preset === 'this_week' ? 'bg-blue-50 text-blue-700 font-semibold border-blue-200 shadow-xs' : 'text-slate-700 hover:bg-slate-50 border-transparent'"
                        >
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Minggu Ini (Senin – Minggu)</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] text-slate-400">7 hari</span>
                                <template x-if="preset === 'this_week'">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </div>
                        </button>

                        <!-- Bulan Ini (Default) -->
                        <button
                            type="button"
                            @click="applyPreset('this_month')"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-all border"
                            :class="preset === 'this_month' ? 'bg-blue-50 text-blue-700 font-semibold border-blue-200 shadow-xs' : 'text-slate-700 hover:bg-slate-50 border-transparent'"
                        >
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <span>Bulan Ini (Default)</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] text-slate-400">{{ now()->format('M Y') }}</span>
                                <template x-if="preset === 'this_month'">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </div>
                        </button>

                        <!-- Bulan Lalu -->
                        <button
                            type="button"
                            @click="applyPreset('last_month')"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-all border"
                            :class="preset === 'last_month' ? 'bg-blue-50 text-blue-700 font-semibold border-blue-200 shadow-xs' : 'text-slate-700 hover:bg-slate-50 border-transparent'"
                        >
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Bulan Lalu</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] text-slate-400">{{ now()->subMonthNoOverflow()->format('M Y') }}</span>
                                <template x-if="preset === 'last_month'">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </div>
                        </button>

                        <!-- Hari Ini -->
                        <button
                            type="button"
                            @click="applyPreset('today')"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-all border"
                            :class="preset === 'today' ? 'bg-blue-50 text-blue-700 font-semibold border-blue-200 shadow-xs' : 'text-slate-700 hover:bg-slate-50 border-transparent'"
                        >
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-cyan-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <span>Hari Ini</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] text-slate-400">{{ now()->format('d M') }}</span>
                                <template x-if="preset === 'today'">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </div>
                        </button>
                    </div>

                    <!-- Kustom Tanggal -->
                    <div class="pt-3 border-t" style="border-color: var(--border-color);">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-2 px-1">Kustom Rentang Tanggal</p>
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <div>
                                <label class="block text-[10px] font-medium text-slate-500 mb-1">Dari Tanggal</label>
                                <input
                                    type="date"
                                    x-model="startDate"
                                    class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 bg-slate-50/70 text-slate-800 outline-none focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15 transition-all"
                                >
                            </div>
                            <div>
                                <label class="block text-[10px] font-medium text-slate-500 mb-1">Sampai Tanggal</label>
                                <input
                                    type="date"
                                    x-model="endDate"
                                    class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 bg-slate-50/70 text-slate-800 outline-none focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15 transition-all"
                                >
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="applyCustom()"
                            class="w-full py-2 px-3 rounded-xl text-xs font-semibold text-white shadow-xs transition-all hover:opacity-95 active:scale-[0.99] flex items-center justify-center gap-1.5"
                            style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8);"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Terapkan Rentang Kustom</span>
                        </button>
                    </div>
                </div>
            </div>

<!-- User Profile Dropdown -->
            <div class="relative ml-2" id="userDropdownContainer">
                <button
                    class="flex items-center gap-3 p-1.5 pr-3 rounded-xl border border-transparent hover:bg-gray-100 transition-colors"
                    onclick="toggleUserDropdown(event)"
                    aria-label="User menu"
                    aria-expanded="false"
                    id="userMenuBtn"
                >
                    <!-- Avatar -->
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center text-blue-700 shadow-sm flex-shrink-0 border border-blue-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    
                    <!-- User Info -->
                    <div class="text-left hidden sm:block">
                        <p class="text-sm font-semibold leading-tight" style="color: var(--text-primary);">{{ auth()->user()->name }}</p>
                        <p class="text-xs font-medium mt-0.5" style="color: var(--text-muted);">{{ auth()->user()->role_display_name }}</p>
                    </div>
                    
                    <!-- Chevron -->
                    <svg class="w-4 h-4 ml-1 hidden sm:block transition-transform duration-200" id="userDropdownChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div 
                    class="absolute right-0 mt-2 w-48 rounded-xl shadow-lg bg-white border py-1 hidden"
                    style="border-color: var(--border-color); box-shadow: var(--shadow-md); z-index: 100;"
                    id="userDropdownMenu"
                >
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Profile Settings
                    </a>
                    <div class="border-t my-1" style="border-color: var(--border-color);"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors text-left">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
