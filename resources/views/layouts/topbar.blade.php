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

            <!-- Date Range -->
            <div class="hidden sm:flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium"
                 style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span id="currentDate">{{ now()->format('M 1 – M t, Y') }}</span>
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
