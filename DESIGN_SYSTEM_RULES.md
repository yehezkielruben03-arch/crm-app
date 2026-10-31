# 🎨 CRM Analytics — Design System & Engineering Rules

Dokumen ini mendokumentasikan secara komprehensif arsitektur *Design System*, token visual, komponen Blade, pendekatan styling, dan konvensi antarmuka pengguna pada codebase **CRM Analytics**. Dokumen ini dirancang sebagai acuan integrasi Figma MCP, Code Connect, dan standar pengembangan antarmuka bebas *AI-slop*.

---

## 1. Token Definitions

### Lokasi & Format
Design tokens didefinisikan sebagai CSS Custom Properties (`:root`) di [`public/css/style.css`](file:///c:/Users/ruben/Downloads/project/crm-app/public/css/style.css):

```css
:root {
    /* Palette Warna Permukaan & Background */
    --bg-primary:      #f0f4f8;   /* Slate light canvas */
    --bg-secondary:    #e8edf3;   /* Input backgrounds & subtle cards */
    --bg-card:         #ffffff;   /* Pure white card container */
    --bg-card-hover:   #f8fafc;
    --border-color:    #d1dbe8;   /* Soft slate border */
    
    /* Tipografi & Kontras Teks */
    --text-primary:    #0f172a;   /* Slate 900 (High contrast heading/body) */
    --text-secondary:  #475569;   /* Slate 600 (Labels & description) */
    --text-muted:      #94a3b8;   /* Slate 400 (Placeholders & icons) */

    /* Accent & Semantic System */
    --accent-blue:     #2563eb;   /* Primary Action & Brand Identity */
    --accent-cyan:     #0891b2;   /* Secondary Info & Telemetry */
    --accent-emerald:  #059669;   /* Success, GOAL status, Active badges */
    --accent-amber:    #d97706;   /* Warning, Pending Approval */
    --accent-rose:     #e11d48;   /* Danger, Cancelled status, Destructive */
    --accent-violet:   #7c3aed;   /* Special features & Analytics highlights */

    /* Elevation & Shadows */
    --shadow-sm:  0 1px 3px rgba(15, 23, 42, 0.08);
    --shadow-md:  0 4px 12px rgba(15, 23, 42, 0.10);
    --shadow-lg:  0 12px 32px rgba(15, 23, 42, 0.12);
}
```

### Tipografi
1. **Headings (`h1` s/d `h6`):** `'Space Grotesk', sans-serif` (Google Fonts). Karakter tegas, teknikal, modern, dan bernuansa analitik data.
2. **Body & Interface:** `'Inter', sans-serif`. Sangat mudah dibaca pada ukuran 11px–14px.

### Skala Radius Sudut (Border Radius)
- **Cards & Modal Containers:** `16px` (`rounded-2xl`).
- **Interactive Controls (Buttons, Inputs, Selects, Popovers):** `12px` (`rounded-xl`).
- **Small Badges & Pills:** `9999px` (`rounded-full`).

---

## 2. Component Library

### Arsitektur Komponen
Komponen UI modular dibangun menggunakan **Laravel Blade Components** yang berlokasi di [`resources/views/components/`](file:///c:/Users/ruben/Downloads/project/crm-app/resources/views/components/):

- [`custom-select.blade.php`](file:///c:/Users/ruben/Downloads/project/crm-app/resources/views/components/custom-select.blade.php): Dropdown select kustom dengan Alpine.js, pencarian terintegrasi, dan *keyboard navigation*.
- [`modal.blade.php`](file:///c:/Users/ruben/Downloads/project/crm-app/resources/views/components/modal.blade.php): Dialog modal dengan backdrop animasi, scroll locking, dan event escape listener.
- [`toast.blade.php`](file:///c:/Users/ruben/Downloads/project/crm-app/resources/views/components/toast.blade.php): Sistem toast notifikasi floating dengan timer otomatis 4 detik.
- [`primary-button.blade.php`](file:///c:/Users/ruben/Downloads/project/crm-app/resources/views/components/primary-button.blade.php) & [`secondary-button.blade.php`](file:///c:/Users/ruben/Downloads/project/crm-app/resources/views/components/secondary-button.blade.php).

### Standar Reaktivitas Micro-Interactions (Alpine.js)
State interaktif ditangani oleh **Alpine.js (v3)** dengan transisi CSS halus:
```html
<div x-data="{ open: false }" @click.outside="open = false">
    <button @click="open = !open">...</button>
    <div x-show="open" 
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100">
        <!-- Content -->
    </div>
</div>
```

---

## 3. Frameworks & Libraries

- **Backend:** Laravel 12 / PHP 8.2+
- **Templating:** Blade Template Engine
- **Styling Utility:** Tailwind CSS v3 / v4 (`@tailwindcss/vite`)
- **JavaScript UI:** Alpine.js v3 (Ringan, declarative, tanpa beban runtime React/Vue yang berat)
- **Visualisasi Grafik:** Chart.js
- **Datepicker:** Flatpickr (Custom styled matching `--border-color` & `--accent-blue`)
- **Bundler:** Vite v7 (`laravel-vite-plugin`)

---

## 4. Asset Management

- **Public Static Assets:** Disimpan di `public/` (`public/images/`, `public/css/style.css`).
- **Compiled Assets:** Disusun oleh Vite via `@vite(['resources/css/app.css', 'resources/js/app.js'])`.
- **Favicons & Logos:** `public/images/logo.png`.

---

## 5. Icon System

- **Format:** Pure Inline SVG berbasis [Heroicons v2](https://heroicons.com/).
- **Konvensi Rendering:**
  ```html
  <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="..."/>
  </svg>
  ```
- **Anti-Pattern (AI Slop Warning):**
  - ❌ **DILARANG** menggunakan emoji acak warna-warni (misal `⚡`, `📅`, `📍`) di dalam komponen navigasi atau kontrol formulir profesional.
  - ✅ **WAJIB** menggunakan stroke SVG monokrom atau semantik dengan stroke-width terstandardisasi (`1.5` atau `2`).

---

## 6. Styling Approach & Anti-Slop Guidelines

1. **Utility-First + CSS Variables:** Gunakan kelas Tailwind CSS dikombinasikan dengan variabel CSS semantik (`var(--bg-card)`, `var(--text-primary)`, `var(--border-color)`).
2. **Micro-interactions:** Setiap elemen interaktif wajib memiliki:
   - Hover feedback: `hover:bg-slate-50` atau `hover:opacity-95`.
   - Active tactile feedback: `active:scale-[0.99]` atau `active:scale-95`.
   - Focus ring aksesibel: `focus:ring-2 focus:ring-blue-500/15 focus:border-blue-500`.
3. **Responsive Standards:**
   - `< 640px` (Mobile): Komponen tetap proporsional (teks label panjang di-hide via `hidden md:inline`, menyisakan ikon kalender 36x36px).
   - Dropdown Popover wajib dibatasi `max-w-[92vw]` untuk mencegah horizontal scroll pada layar kecil.

---

## 7. Project Structure

```
crm-app/
├── app/
│   ├── Http/Controllers/     # DashboardController, RfqController, CustomerController
│   ├── Models/                  # User, Rfq, Customer, PurchaseOrder, etc.
│   └── Traits/                  # HandlesDateRangeFilter.php (Global logic)
├── database/
│   ├── migrations/              # Database schema & indexing
│   └── seeders/                 # TestingDateFilterSeeder, UserSeeder
├── public/
│   └── css/style.css            # Root design tokens & global CSS resets
├── resources/
│   ├── css/app.css              # Tailwind layer components & utility classes
│   └── views/
│       ├── components/          # Reusable Blade components
│       ├── layouts/             # app.blade.php, topbar.blade.php, sidebar.blade.php
│       ├── dashboard.blade.php  # Dashboard view
│       ├── rfqs/                # RFQ index & forms
│       └── customers/           # Customer database views
└── package.json                 # Tailwind, Alpine, Vite bundler
```
