<!-- Sidebar Overlay (mobile only) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMobileSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">

    <!-- Logo -->
    <div class="sidebar-logo-btn mb-8" onclick="toggleDesktopSidebar()" title="Toggle sidebar" id="sidebarToggleBtn">
        <!-- CRM Icon -->
        <div class="sidebar-logo-icon">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
        </div>

        <!-- Logo text -->
        <div class="sidebar-logo-text">
            <span class="font-bold text-sm tracking-tight block leading-tight" style="color: var(--text-primary);">CRM Analytics</span>
            <p class="text-xs leading-tight" style="color: var(--text-muted);">Enterprise Suite</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 space-y-1" role="navigation" aria-label="Main navigation">

        <a href="{{ route('dashboard') }}" class="sidebar-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span class="sidebar-label">Dashboard</span>
        </a>

        @if(auth()->user()->hasPermission('Customer Access') || in_array(auth()->user()->role, ['Admin', 'Super Admin', 'Sales', 'Leader']))
        <a href="{{ route('customers.index') }}" class="sidebar-nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" title="Customers">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span class="sidebar-label">Customers</span>
        </a>

        <a href="{{ route('rfq.index') }}" class="sidebar-nav-link {{ request()->routeIs('rfq.*') ? 'active' : '' }}" title="RFQ">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span class="sidebar-label">RFQ</span>
        </a>

        <a href="{{ route('quo.index') }}" class="sidebar-nav-link {{ request()->routeIs('quo.*') ? 'active' : '' }}" title="Quotations">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            <span class="sidebar-label">Quotations</span>
        </a>

        @if(!auth()->user()->isSales())
        <!-- Vendor Menu -->
        <a href="{{ route('vendors.index') }}" class="sidebar-nav-link {{ request()->routeIs('vendors.*') ? 'active' : '' }}" title="Vendor">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <span class="sidebar-label">Vendor</span>
        </a>

        <!-- Mainpower Menu -->
        <a href="{{ route('mainpowers.index') }}" class="sidebar-nav-link {{ request()->routeIs('mainpowers.*') ? 'active' : '' }}" title="Portal Mainpower">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span class="sidebar-label">Portal Mainpower</span>
        </a>

        <a href="{{ route('po.index') }}" class="sidebar-nav-link {{ request()->routeIs('po.*') ? 'active' : '' }}" title="Purchase Orders">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="sidebar-label">Purchase Orders</span>
        </a>
        @else
        <a href="{{ route('sales.history') }}" class="sidebar-nav-link {{ request()->routeIs('sales.history') ? 'active' : '' }}" title="History Penjualan Sales Marketing">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="sidebar-label">History Penjualan Sales Marketing</span>
        </a>
        @endif
        @endif

        @if(auth()->user()->hasPermission('Approval'))
        <a href="{{ route('approvals.index') }}" class="sidebar-nav-link {{ request()->routeIs('approvals.*') ? 'active' : '' }}" title="Approvals">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="sidebar-label">Approvals</span>
        </a>
        @endif

        @if(auth()->user()->hasPermission('Report Access'))
        <a href="{{ route('analytics.index') }}" class="sidebar-nav-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}" title="Revenue">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="sidebar-label">Revenue</span>
        </a>
        @endif

        @if(auth()->user()->isAdminOrAbove())
        <a href="{{ route('users.index') }}" class="sidebar-nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" title="User Management">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span class="sidebar-label">User Management</span>
        </a>
        @endif

    </nav>

    <!-- User Profile -->
    <div class="mt-auto pt-4" style="border-top: 1px solid var(--border-color);">
        <div class="sidebar-user-row flex items-center gap-3 p-2 rounded-xl cursor-pointer"
             onmouseenter="this.style.background='rgba(37,99,235,0.05)'"
             onmouseleave="this.style.background=''">
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-400 to-cyan-500 flex items-center justify-center text-sm font-bold text-white shadow-sm flex-shrink-0">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="sidebar-user-info flex-1 min-w-0">
                <p class="font-semibold text-sm truncate" style="color: var(--text-primary);">{{ auth()->user()->name }}</p>
                <p class="text-xs truncate" style="color: var(--text-muted);">{{ auth()->user()->role_display_name }}</p>
            </div>
        </div>
    </div>
</aside>
