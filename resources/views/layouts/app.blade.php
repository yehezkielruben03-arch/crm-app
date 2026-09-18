<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Flatpickr CSS (Datepicker) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Alpine x-cloak fix */
        [x-cloak] { display: none !important; }

        /* Custom UI match for Flatpickr */
        .flatpickr-calendar {
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color, #e5e7eb);
            font-family: 'Inter', sans-serif;
        }
        .flatpickr-day.selected {
            background: var(--accent-blue, #2563eb) !important;
            border-color: var(--accent-blue, #2563eb) !important;
        }
    </style>
</head>
<body>

    <!-- Decorative Background -->
    <div class="bg-pattern" aria-hidden="true"></div>
    <div class="grid-overlay" aria-hidden="true"></div>

    <div class="layout-root">

        <!-- Sidebar -->
        @include('layouts.sidebar')

        <!-- Main Content Area -->
        <main class="main-content" id="mainContent">

            <!-- Topbar -->
            @include('layouts.topbar')

            <!-- Dashboard Content -->
            <div class="dashboard-scroll-area">
                <div class="p-6 space-y-6">
                    {{ $slot }}
                </div>
            </div>

        </main>
    </div>

    <script>
        // Desktop Sidebar Toggle
        function toggleDesktopSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 1024) {
                sidebar.classList.toggle('collapsed');
            }
        }

        // Mobile Sidebar Toggle
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }

        // Dropdown Toggle
        function toggleUserDropdown(e) {
            e.stopPropagation();
            const menu = document.getElementById('userDropdownMenu');
            const btn = document.getElementById('userMenuBtn');
            const chevron = document.getElementById('userDropdownChevron');

            const isExpanded = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', !isExpanded);
            menu.classList.toggle('hidden');

            if (!isExpanded) {
                chevron.style.transform = 'rotate(180deg)';
                btn.style.background = 'rgba(37,99,235,0.05)';
            } else {
                chevron.style.transform = 'rotate(0deg)';
                btn.style.background = 'transparent';
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            const container = document.getElementById('userDropdownContainer');
            if (container && !container.contains(e.target)) {
                const menu = document.getElementById('userDropdownMenu');
                const btn = document.getElementById('userMenuBtn');
                const chevron = document.getElementById('userDropdownChevron');

                if (!menu.classList.contains('hidden')) {
                    menu.classList.add('hidden');
                    btn.setAttribute('aria-expanded', 'false');
                    btn.style.background = 'transparent';
                    chevron.style.transform = 'rotate(0deg)';
                }
            }
        });

        </script>

    <!-- Flatpickr JS (Datepicker) -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr(".datepicker", {
                locale: "id",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                allowInput: true
            });
        });
    </script>

    <script>
    /* ═══════════════════════════════════════════════════════════
     * Alpine.js Global Components
     * ═══════════════════════════════════════════════════════════ */

    /**
     * customSelect() - Komponen dropdown kustom (Select styler)
     */
    function customSelect({ open, selected, options, placeholder }) {
        return {
            open,
            selected,
            options,
            placeholder,
            selectedLabel() {
                return this.selected && this.options[this.selected]
                    ? this.options[this.selected]
                    : this.placeholder;
            }
        }
    }

    /**
     * regionSelector() - Dependent Select Wilayah Indonesia
     * ──────────────────────────────────────────────────────
     * ALGORITMA BERANTAI (Cascade):
     *
     * Step 1: User pilih Provinsi
     *   → Fetch /api/region/cities?province=...
     *   → Tampilkan daftar Kota/Kab, reset Kec/Kel/KodePos
     *
     * Step 2: User pilih Kota/Kabupaten
     *   → Fetch /api/region/districts?province=...&city=...
     *   → Tampilkan daftar Kecamatan, reset Kel/KodePos
     *
     * Step 3: User pilih Kecamatan
     *   → Fetch /api/region/villages?...&district=...
     *   → Tampilkan daftar Kelurahan (data sudah include kode pos)
     *
     * Step 4: User pilih Kelurahan  ← AUTO-FILL terjadi di sini
     *   → Ambil postal_code dari array villages di memory (no HTTP!)
     *   → Set field Kode Pos secara otomatis
     *
     * MENGAPA BERANTAI & BUKAN SATU REQUEST?
     * Data 83.000+ kelurahan Indonesia terlalu besar untuk dimuat
     * sekaligus. Dengan berantai, kita hanya load data yang perlu.
     * ──────────────────────────────────────────────────────────
     */
    function regionSelector() {
        return {
            province:   '',
            city:       '',
            district:   '',
            village:    '',
            postalCode: '',

            cities:    [],
            districts: [],
            villages:  [],
            postalCodeOptions: [],

            loadingCities:    false,
            loadingDistricts: false,
            loadingVillages:  false,

            async onProvinceChange() {
                this.city = ''; this.district = ''; this.village = ''; this.postalCode = '';
                this.cities = []; this.districts = []; this.villages = [];
                if (!this.province) return;
                this.loadingCities = true;
                try {
                    const r = await fetch('/api/region/cities?province=' + encodeURIComponent(this.province));
                    this.cities = await r.json();
                } catch(e) { console.error('region cities:', e); }
                this.loadingCities = false;
            },

            async onCityChange() {
                this.district = ''; this.village = ''; this.postalCode = '';
                this.districts = []; this.villages = [];
                if (!this.city) return;
                this.loadingDistricts = true;
                try {
                    const r = await fetch('/api/region/districts?province=' + encodeURIComponent(this.province) + '&city=' + encodeURIComponent(this.city));
                    this.districts = await r.json();
                } catch(e) { console.error('region districts:', e); }
                this.loadingDistricts = false;
            },

            async onDistrictChange() {
                this.village = ''; this.postalCode = '';
                this.villages = [];
                if (!this.district) return;
                this.loadingVillages = true;
                try {
                    const r = await fetch('/api/region/villages?province=' + encodeURIComponent(this.province) + '&city=' + encodeURIComponent(this.city) + '&district=' + encodeURIComponent(this.district));
                    this.villages = await r.json();
                } catch(e) { console.error('region villages:', e); }
                this.loadingVillages = false;
            },

            async onVillageChange() {
                if (!this.village) { this.postalCode = ''; this.postalCodeOptions = []; return; }
                const found = this.villages.find(v => v.name === this.village);
                if (found && found.postal_code) {
                    this.postalCode = found.postal_code;
                    this.postalCodeOptions = [found.postal_code];
                } else {
                    // Try to fetch from public kodepos API
                    this.postalCode = 'Mencari...';
                    this.postalCodeOptions = [];
                    try {
                        const r = await fetch('https://kodepos.vercel.app/search?q=' + encodeURIComponent(this.village));
                        const data = await r.json();
                        if (data && data.data && data.data.length > 0) {
                            // Normalize strings by removing all spaces and non-alphanumeric chars
                            const normalize = (str) => str.toLowerCase().replace(/[^a-z0-9]/g, '');
                            const normalizedVillage = normalize(this.village);
                            const normalizedDistrict = normalize(this.district);

                            // Find all exact or fuzzy matches
                            let matches = data.data.filter(d => {
                                const dVillage = normalize(d.village);
                                const dDistrict = normalize(d.district);
                                return dVillage === normalizedVillage ||
                                    (normalizedVillage.includes(dVillage) && normalizedDistrict.includes(dDistrict)) ||
                                    (dVillage.includes(normalizedVillage) && dDistrict.includes(normalizedDistrict));
                            });
                            
                            // Get unique postal codes from matches
                            let uniqueCodes = [...new Set(matches.map(m => m.code))];
                            
                            if (uniqueCodes.length > 0) {
                                this.postalCodeOptions = uniqueCodes;
                                this.postalCode = uniqueCodes[0];
                            } else {
                                this.postalCode = '';
                            }
                        } else {
                            this.postalCode = '';
                        }
                    } catch (e) {
                        this.postalCode = '';
                    }
                }
            }
        }
    }
    </script>

    <x-toast />

    <!-- Toast Popup Notifikasi dirender di dalam komponen lonceng (layouts/topbar.blade.php) -->
    <!-- Alpine -> notificationSystem() di bawah -->

    <!-- AlpineJS Logic for Realtime Notifications -->
    <script>
        function notificationSystem() {
            return {
                open: false,
                unreadCount: parseInt("{{ \Illuminate\Support\Facades\Cache::get('notif:unread:' . auth()->id()) ?? (auth()->user() ? auth()->user()->unreadNotificationsCount() : 0) }}") || 0,
                latestNotifications: [],
                toasts: [],
                toastCounter: 0,
                maxSeenId: 0,
                initialized: false,
                pollingTimer: null,

                initPolling() {
                    // Fetch pertama langsung diserap sebagai baseline (tidak memunculkan toast lama)
                    this.poll();
                    // Polling real-time: interval acak 2-5 detik per siklus (spec: 2-5 detik,
                    // jitter menghindari seluruh client memukul server di detik yang sama)
                    this.schedulePoll();
                },

                schedulePoll() {
                    const delay = 2000 + Math.random() * 3000;
                    this.pollingTimer = setTimeout(() => {
                        this.poll();
                        this.schedulePoll();
                    }, delay);
                },

                async poll() {
                    try {
                        const response = await fetch('/api/notifications/poll');
                        if (!response.ok) return;
                        const data = await response.json();

                        this.latestNotifications = data.latest;
                        const maxInData = this.latestNotifications.reduce((m, n) => Math.max(m, n.id), 0);

                        if (!this.initialized) {
                            // Polling pertama: seed baseline (badge sesuai unread yang ada, tanpa toast lama)
                            this.initialized = true;
                            this.maxSeenId = maxInData;
                            this.unreadCount = data.unread_count;
                            return;
                        }

                        // Hanya notif yang id-nya lebih besar dari yang pernah dilihat = BETUL-BETUL BARU
                        const fresh = this.latestNotifications.filter(n => n.id > this.maxSeenId);

                        if (fresh.length > 0) {
                            fresh.forEach(n => {
                                this.showToast(n.title, n.message);
                            });
                            this.maxSeenId = Math.max(this.maxSeenId, maxInData);
                            // KUNCI: tanda merah (badge) baru aktif SETELAH 4 detik, saat toast selesai tampil
                            const targetUnread = data.unread_count;
                            setTimeout(() => this.commitBadge(targetUnread), 4000);
                        }
                    } catch (e) {
                        console.error('Polling error', e);
                    }
                },

                commitBadge(value) {
                    if (value > this.unreadCount) {
                        this.unreadCount = value;
                    }
                },

                showToast(title, message) {
                    const id = this.toastCounter++;
                    this.toasts.push({ id, title, message, visible: true });
                    // Durasi toast: 4 detik, lalu langsung menghilang dari atas
                    setTimeout(() => this.removeToast(id), 4000);
                },

                removeToast(id) {
                    const index = this.toasts.findIndex(t => t.id === id);
                    if (index !== -1) {
                        this.toasts[index].visible = false;
                        setTimeout(() => {
                            this.toasts = this.toasts.filter(t => t.id !== id);
                        }, 300);
                    }
                },

                async markAllRead() {
                    try {
                        const response = await fetch('/notifications/mark-all-read', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json'
                            }
                        });
                        if (response.ok) {
                            this.unreadCount = 0;
                            this.maxSeenId = 0;
                            this.poll();
                        }
                    } catch (e) {
                        console.error('Error marking read', e);
                    }
                },

                async openNotification(id, link) {
                    try {
                        await fetch('/notifications/' + id + '/read', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json'
                            }
                        });
                    } catch (e) {
                        // Meski request gagal, tetap lanjutkan navigasi
                        console.error('Error marking read', e);
                    }
                    if (id > this.maxSeenId) this.maxSeenId = id;
                    if (this.unreadCount > 0) this.unreadCount--;
                    window.location.href = link || '/dashboard';
                }
            }
        }
    </script>
</body>
</html>
