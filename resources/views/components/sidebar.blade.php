<!-- Sidebar Overlay (mobile) -->
<div x-show="sidebarOpen" 
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm md:hidden"
      @click="sidebarOpen = false" 
      aria-hidden="true"></div>

<!-- Sidebar -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 flex flex-col w-64 h-screen transition-transform duration-300 ease-in-out bg-indigo-900 shadow-xl md:static md:translate-x-0 text-indigo-100">
    
    <!-- Logo Area -->
    <div class="flex items-center gap-3 px-6 py-5 bg-indigo-950/50">
        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-500/30">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
        </div>
        <div>
            <span class="block text-sm font-bold tracking-wider text-white uppercase">CRM Analytics</span>
            <span class="block text-xs text-indigo-300">Enterprise Suite</span>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto custom-scrollbar">
        
        <!-- Dashboard (Semua Role Bisa) -->
        <a href="{{ route('dashboard') }}" 
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white font-medium shadow-md' : 'hover:bg-indigo-800/50 text-indigo-200 hover:text-white' }}">
            <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            Dashboard
        </a>

        @if(auth()->user()->hasPermission('Customer Access'))
            <p class="px-3 pt-4 pb-2 text-xs font-semibold tracking-wider text-indigo-400 uppercase">Sales Marketing & Clients</p>
            
            <a href="{{ route('customers.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('customers.*') ? 'bg-indigo-600 text-white font-medium shadow-md' : 'hover:bg-indigo-800/50 text-indigo-200 hover:text-white' }}">
                <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Customers
            </a>

            @if(auth()->user()->isAdminOrAbove())
            <!-- Purchase Orders (Admin/Leader) -->
            <a href="{{ route('po.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('po.*') ? 'bg-indigo-600 text-white font-medium shadow-md' : 'hover:bg-indigo-800/50 text-indigo-200 hover:text-white' }}">
                <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Purchase Orders
            </a>
            @else
            <!-- History (Sales Marketing) -->
            <a href="{{ route('sales.history') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('sales.history') ? 'bg-indigo-600 text-white font-medium shadow-md' : 'hover:bg-indigo-800/50 text-indigo-200 hover:text-white' }}">
                <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                History
            </a>
            @endif

            <!-- RFQ -->
            <a href="{{ route('rfq.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('rfq.*') ? 'bg-indigo-600 text-white font-medium shadow-md' : 'hover:bg-indigo-800/50 text-indigo-200 hover:text-white' }}">
                <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                RFQ
            </a>

            <!-- Quotation -->
            <a href="{{ route('quo.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('quo.*') ? 'bg-indigo-600 text-white font-medium shadow-md' : 'hover:bg-indigo-800/50 text-indigo-200 hover:text-white' }}">
                <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Quotation
            </a>
        @endif

        @if(auth()->user()->hasPermission('Approval'))
            <p class="px-3 pt-4 pb-2 text-xs font-semibold tracking-wider text-indigo-400 uppercase">Approvals</p>
            <a href="{{ route('approvals.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('approvals.*') ? 'bg-indigo-600 text-white font-medium shadow-md' : 'hover:bg-indigo-800/50 text-indigo-200 hover:text-white' }}">
                <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Pending Approvals
            </a>
        @endif

        @if(auth()->user()->hasPermission('Report Access'))
            <p class="px-3 pt-4 pb-2 text-xs font-semibold tracking-wider text-indigo-400 uppercase">Analytics</p>
            <a href="{{ route('analytics.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('analytics.*') ? 'bg-indigo-600 text-white font-medium shadow-md' : 'hover:bg-indigo-800/50 text-indigo-200 hover:text-white' }}">
                <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Revenue Report
            </a>
        @endif

        @if(auth()->user()->isAdminOrAbove())
            <p class="px-3 pt-4 pb-2 text-xs font-semibold tracking-wider text-indigo-400 uppercase">Settings</p>
            <a href="{{ route('users.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white font-medium shadow-md' : 'hover:bg-indigo-800/50 text-indigo-200 hover:text-white' }}">
                <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                User Management
            </a>
        @endif
    </nav>

    <!-- User Profile Area -->
    <div class="p-4 mt-auto border-t border-indigo-800 bg-indigo-950/20">

        {{-- Lonceng Notifikasi (cached 60s) --}}
        @php $unread = \Illuminate\Support\Facades\Cache::remember('notif:unread:' . auth()->id(), 60, fn() => auth()->user()->unreadNotificationsCount()); @endphp
        <a href="{{ route('notifications.index') }}" class="flex items-center justify-between px-3 py-2 mb-2 rounded-lg text-indigo-200 hover:bg-indigo-800/50 hover:text-white transition-colors group">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span class="text-sm">Notifikasi</span>
            </div>
            @if($unread > 0)
            <span class="flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full">
                {{ $unread > 9 ? '9+' : $unread }}
            </span>
            @endif
        </a>
        <div class="flex items-center gap-3 p-2 transition-colors rounded-lg cursor-pointer hover:bg-indigo-800/50 group">
            <div class="flex items-center justify-center w-10 h-10 text-sm font-bold text-white rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 shadow-inner flex-shrink-0">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-indigo-300 truncate">{{ auth()->user()->role_display_name }}</p>
            </div>
            <!-- Logout Button/Icon -->
            <form method="POST" action="{{ route('logout') }}" class="flex-shrink-0">
                @csrf
                <button type="submit" class="p-1.5 text-indigo-300 transition-colors rounded hover:text-white hover:bg-indigo-700" title="Logout">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>
