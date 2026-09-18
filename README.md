# 🏢 CRM Analyst — B2B Enterprise Sales & Quotation Management System

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-3.4-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

A comprehensive, role-based B2B CRM and Quotation Management Platform designed for enterprise procurement, sales tracking, and cost analysis (*Harga Pokok Penjualan / HPP*). Built for streamlined coordination between **Sales Marketing**, **Admin Purchase**, and **Management/Leaders**.

---

## 🌟 Key Highlights & Business Capabilities

### 1. 🔐 Strict Role-Based Access Control (RBAC) & Confidentiality
- **4 Granular Roles**: Super Admin, Leader / Sales Manager, Admin Purchase, and Sales Marketing.
- **HPP Secrecy Rule**: Vendor cost prices, modal calculations, and profit margins are **strictly hidden** from the Sales team to preserve corporate pricing confidentiality.
- Protected routes and controllers enforce permission checks on all actions.

### 2. 📑 3-Category RFQ Engine & Auto-Numbering
- Handles multi-item inquiries categorized into:
  1. **Hardware / Devices**
  2. **Services / Jasa Instalasi**
  3. **Materials / Consumables**
- Automated chronological standard document numbering (e.g., `YYMMDD-XXXX`).
- Multi-attachment uploads for client Terms of Reference (TOR) and technical specs.

### 3. 🧮 Enterprise HPP Costing & Pricing Engine
- **Admin Purchase Portal**: Input vendor base prices, vendor delivery fees, and custom company delivery (*Ongkir Pedia*).
- **Flexible Markup**: Percentage margin (`%`) or fixed nominal margin (`Rp`).
- **Smart Ceiling Rounding**: Mathematical rounding up to the nearest thousand for clean client-facing quotes.
- **Mainpower Portal**: Automated technician day-rate formulas factoring base salary, meal allowance, overtime, BPJS Kesehatan, and BPJS Ketenagakerjaan.

### 4. 🔄 Multi-Level Leader Approval & "Super Revision" Cycle
- **Approval Queue**: Leaders can review quotes, request revisions with detailed feedback, or give instant authorization.
- **Revisi QTY & Scope**: Sales can modify quantities, remove canceled items, and add new line items after initial client discussions.
- **Historical Snapshots**: Version 1 and Version 2 costing snapshots recorded in database for full financial audit trails.
- **Dynamic Red "REVISI" Badge**: Automatically generated on subsequent quotation PDFs.

### 5. 📄 Official Document Generation (Bilingual PDF)
- **Quotation PDF**: Executive client-facing proposal with bilingual Terms & Conditions, official corporate header/footer, and digital signatory.
- **Purchase Order (PO) to Vendor**: Supplier order generation with item specifications and delivery instructions.
- **Client PO Verification**: Sales uploads customer PO -> Admin verifies document -> Leader confirms -> Deal status marked **GOAL (Won)**.

### 6. 🤝 Customer Relationship Lifecycle & Anti-Duplication
- Real-time duplicate company and contact detection via AJAX.
- Multi-address billing and shipping with dependent Indonesian administrative region selector (Province -> Regency -> District -> Village).
- Inline PIC creation directly inside the RFQ creation form without page reloads.
- **Sales Resignation Migration**: Transfer an entire client portfolio and active deals from a departing sales rep to a new rep in one click.

### 7. 📊 Executive Analytics & KPI Progress
- Real-time conversion rates (Won/Lost Ratio), Total Revenue Pipeline, and Average Margin Realization.
- Dynamic monthly target progress bars per sales executive.
- Polling-based live bell notification center for urgent approvals and state changes.

---

## 🛠️ Technology Stack

- **Framework**: Laravel 12.x
- **Language**: PHP 8.2 / 8.3 (with strict type hinting & custom polyfills)
- **Database**: MySQL 8 / MariaDB 10.5+
- **Frontend / Styling**: Tailwind CSS, Alpine.js, Blade Engine, Vite
- **Document Rendering**: Barryvdh DomPDF
- **Spreadsheet Engine**: Maatwebsite Excel 3.1
- **Deployment Targets**: Linux / cPanel (CloudLinux + LiteSpeed), Docker Sail, Cloudflare Tunnel

---

## 🚀 Getting Started Locally

### Prerequisites
- PHP >= 8.2 with extensions: `pdo_mysql`, `mbstring`, `fileinfo`, `intl`, `zip`, `bcmath`, `curl`, `dom`, `gd`
- Composer 2.x
- Node.js (v18+ or v20+ LTS) & NPM
- MySQL / MariaDB

### Installation Steps

1. **Clone the repository:**
   ```bash
   git clone https://github.com/your-username/crm-analyst.git
   cd crm-analyst
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Configure Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Edit `.env` to match your local database credentials.*

4. **Run Migrations & Seeders:**
   ```bash
   php artisan migrate --seed
   ```

5. **Build Frontend Assets:**
   ```bash
   npm run build
   # or for development: npm run dev
   ```

6. **Start the Local Development Server:**
   ```bash
   php artisan serve
   ```
   Access the dashboard at `http://127.0.0.1:8000`.

---

## 🔑 Default Seeded Accounts

| Role | Email | Password | Primary Functions |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `ruben@crm.com` | `password123` | System Admin, User Management, Customer Migration |
| **Leader / Manager** | `laras@crm.com` | `password123` | Costing Approval, Goal Confirmation, Analytics |
| **Admin Purchase** | `admin@crm.com` | `password123` | Vendor Master, HPP Calculation, Supplier POs |
| **Sales Marketing** | `ade@crm.com` | `password123` | Customer Leads, RFQ Request, Client PO Upload |

---

## 📄 License
This software is licensed under the [MIT License](LICENSE).
