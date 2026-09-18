<x-app-layout>

    <style>
        /* ════════════════════════════════════════════════════════
           RFQ Detail — Item Grid
           Satu blueprint CSS Grid yang sama persis untuk header
           dan semua baris data → garis kolom sejajar dari atas
           ke bawah, tidak ada teks yang bertabrakan.
           ════════════════════════════════════════════════════════ */
        .rfq-grid { display: grid; min-width: 700px; }

        /* Layout kolom data rfq */
        .rfq-grid--admin .rfq-grid__row { grid-template-columns: 40px minmax(220px, 2fr) 80px 100px 150px; }
        .rfq-grid--sales .rfq-grid__row { grid-template-columns: 40px minmax(220px, 2fr) 80px 100px; }

        .rfq-grid__row {
            display: grid;
            align-items: start;                 /* rata atas: Qty/Satuan/status sejajar dengan Nama Produk */
            transition: background-color 0.15s ease;
        }
        .rfq-grid__row--zebra { background: #f8fafc; }
        .rfq-grid__row:hover { background: rgba(37,99,235,0.04); }

        .rfq-grid__cell {
            padding: 14px 16px;
            font-size: 0.8125rem;
            color: var(--text-secondary);
            border-bottom: 1px solid #e2e8f0;
            min-width: 0;
        }

        /* Header */
        .rfq-grid__row--head { background: #f8fafc; }
        .rfq-grid__row--head .rfq-grid__cell {
            padding: 12px 16px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        /* Nomor urut & Qty */
        .rfq-grid__num { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: var(--text-muted); }

        /* Item Descriptions — bertumpuk vertikal: Nama bold di atas, spesifikasi muted di bawah */
        .rfq-grid__desc-block {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        .rfq-grid__name { font-weight: 600; color: var(--text-primary); white-space: pre-wrap; }

        /* Satuan */
        .rfq-grid__unit {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* Deskripsi — anti jebol ke kolom sebelah */
        .rfq-grid__detail {
            font-size: 0.75rem;
            line-height: 1.5;
            color: var(--text-muted);
            white-space: pre-wrap;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        .rfq-grid__desc {
            word-break: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
            font-size: 0.75rem;
            line-height: 1.5;
            color: var(--text-muted);
            white-space: pre-wrap;
        }

        /* Kolom status admin — status di paling ujung kanan */
        .rfq-grid__status { justify-self: end; text-align: right; }
        .rfq-grid__hpp { display: block; font-size: 0.625rem; line-height: 1.5; color: var(--text-muted); }
        .rfq-grid__hpp-line b { font-weight: 600; color: var(--text-secondary); }
        .rfq-grid__hpp-line--jual, .rfq-grid__hpp-line--jual b { color: var(--accent-blue); }
        .rfq-grid__badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
            font-size: 0.625rem;
            font-style: italic;
            padding: 2px 8px;
            border-radius: 9999px;
            background: rgba(234,179,8,0.08);
            color: #ca8a04;
            border: 1px solid rgba(234,179,8,0.20);
        }

        /* Pembatas sub-kategori — soft gray membentang penuh ke samping */
        .rfq-grid__group {
            background: #f1f5f9;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .rfq-grid__group .rfq-grid__cell { grid-column: 1 / -1; }
        .rfq-grid__group .rfq-grid__group-label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-secondary);
        }
        .rfq-grid__group-count { font-weight: 500; text-transform: none; letter-spacing: 0; color: var(--text-muted); }

        /* Footer total */
        .rfq-grid__foot { background: #f8fafc; }
        .rfq-grid__foot .rfq-grid__cell {
            grid-column: 1 / -1;
            text-align: right;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-secondary);
            border-top: 2px solid #e2e8f0;
            border-bottom: none;
        }
        .rfq-grid__foot-total { font-weight: 700; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: var(--accent-blue); }

        /* Empty state */
        .rfq-grid__empty .rfq-grid__cell {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px 24px;
            color: var(--text-muted);
        }
    </style>

    {{-- Header --}}
    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-start sm:justify-between animate-in" style="animation-delay: 0.05s;">
        <div class="flex items-center gap-3">
            <a href="{{ route('rfq.index') }}"
               class="p-2 rounded-lg transition-colors"
               style="color: var(--text-muted);"
               onmouseenter="this.style.background='#f8fafc'"
               onmouseleave="this.style.background=''">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold font-mono" style="color: var(--text-primary);">{{ $rfq->rfq_number }}</h1>
                    @if($rfq->priority === 'Urgent' || $rfq->priority === 'High Priority')
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full" style="background: rgba(239,68,68,0.1); color: var(--accent-rose); border: 1px solid rgba(239,68,68,0.2);">{{ $rfq->priority }}</span>
                    @elseif($rfq->priority)
                        <span class="px-2 py-0.5 text-[10px] font-medium rounded-full" style="background: rgba(148,163,184,0.1); color: var(--text-secondary); border: 1px solid rgba(148,163,184,0.2);">{{ $rfq->priority }}</span>
                    @endif
                </div>
                <p class="text-sm mt-0.5" style="color: var(--text-muted);">{{ $rfq->customer_name }}</p>
            </div>
        </div>

        <div class="flex gap-2">
            @if(in_array($rfq->status, ['Pending Admin', 'Approved', 'Quotation Created']) && (auth()->user()->hasPermission('CRUD') || auth()->user()->isSales()))
                <a href="{{ route('rfq.edit', $rfq) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5"
                   style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 12px rgba(37,99,235,0.30);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit RFQ
                </a>
            @endif

            @if($rfq->isPendingAdmin() && auth()->user()->isAdminOrAbove())
                <a href="{{ route('rfq.price_form', $rfq) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5"
                   style="background: linear-gradient(135deg, #059669, #0d9488); box-shadow: 0 4px 12px rgba(5,150,105,0.30);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Isi Harga Modal & Margin
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

        {{-- Kiri: Detail Utama & Items --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Info RFQ --}}
            <div class="card p-6 animate-in" style="animation-delay: 0.1s;">
                <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid #e2e8f0;">
                    Informasi RFQ
                </h2>
                <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs mb-0.5" style="color: var(--text-muted);">Customer</dt>
                        <dd class="text-sm font-semibold" style="color: var(--text-primary);">{{ $rfq->customer_name }}</dd>
                        @if($rfq->customer_code)
                        <code class="text-[10px] px-1.5 py-0.5 rounded mt-0.5 inline-block" style="background: rgba(37,99,235,0.06); color: var(--accent-blue);">{{ $rfq->customer_code }}</code>
                        @endif
                    </div>
                    <div>
                        <dt class="text-xs mb-0.5" style="color: var(--text-muted);">Sales Marketing</dt>
                        <dd class="text-sm font-medium" style="color: var(--text-secondary);">{{ $rfq->sales_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs mb-0.5" style="color: var(--text-muted);">Tanggal RFQ</dt>
                        <dd class="text-sm" style="color: var(--text-secondary);">{{ $rfq->rfq_date?->format('d M Y') ?? '-' }}</dd>
                    </div>
                </dl>

                @if($rfq->notes)
                <div class="mt-4 pt-4" style="border-top: 1px solid #e2e8f0;">
                    <dt class="text-xs mb-1" style="color: var(--text-muted);">Catatan</dt>
                    <dd class="text-sm" style="color: var(--text-secondary);">{{ $rfq->notes }}</dd>
                </div>
                @endif

                @if($rfq->revision_notes)
                <div class="mt-4 p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900">
                    <div class="flex items-center gap-1.5 font-bold text-xs text-amber-800 mb-1">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Catatan Revisi Terakhir (Dari Sales):
                    </div>
                    <p class="text-xs text-amber-800 leading-relaxed">{{ $rfq->revision_notes }}</p>
                </div>
                @endif
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 mt-4 pt-4" style="border-top: 1px solid #e2e8f0;">
                    <div>
                        <dt class="text-xs mb-0.5" style="color: var(--text-muted);">Tipe RFQ</dt>
                        <dd class="text-sm font-medium" style="color: var(--text-secondary);">{{ $rfq->type ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs mb-0.5" style="color: var(--text-muted);">Tgl. Dibutuhkan</dt>
                        <dd class="text-sm" style="color: var(--text-secondary);">{{ $rfq->need_date?->format('d M Y') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs mb-0.5" style="color: var(--text-muted);">PIC Tujuan</dt>
                        <dd class="text-sm" style="color: var(--text-secondary);">{{ $rfq->customerContact?->name ?? '-' }} ({{ $rfq->customerContact?->position ?? '-' }})</dd>
                    </div>
                </div>

                @if($rfq->attachment_file_path)
                <div class="mt-4 p-3.5 rounded-xl flex items-center justify-between" style="background: rgba(37,99,235,0.06); border: 1px solid rgba(37,99,235,0.18);">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-blue-100 text-blue-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold" style="color: var(--text-primary);">Lampiran Spesifikasi Teknis (TOR Klien)</p>
                            <p class="text-[11px] font-mono" style="color: var(--text-muted);">{{ $rfq->attachment_file_name ?? basename($rfq->attachment_file_path) }}</p>
                        </div>
                    </div>
                    <a href="{{ route('rfq.attachment', $rfq) }}" target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-blue-600 bg-white border border-blue-200 hover:bg-blue-50 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Buka Dokumen TOR Klien
                    </a>
                </div>
                @endif
            </div>

            {{-- Item RFQ --}}
            <div class="card p-0 overflow-hidden animate-in" style="animation-delay: 0.15s;">
                <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid #e2e8f0;">
                    <div>
                        <h2 class="text-sm font-semibold" style="color: var(--text-secondary);">Detail Item</h2>
                        <p class="text-xs mt-0.5" style="color: var(--text-muted);">{{ $rfq->items->count() }} item &mdash; {{ $rfq->type }}</p>
                    </div>
                    <span class="text-xs font-semibold px-3 py-1 rounded-full" style="background: rgba(37,99,235,0.08); color: var(--accent-blue); border: 1px solid rgba(37,99,235,0.15);">
                        {{ $rfq->items->count() }} Item
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <div class="rfq-grid {{ auth()->user()->isAdminOrAbove() ? 'rfq-grid--admin' : 'rfq-grid--sales' }}">

                        {{-- HEADER — blueprint grid yang sama persis dengan baris data --}}
                        <div class="rfq-grid__row rfq-grid__row--head">
                            <span class="rfq-grid__cell text-center">#</span>
                            <span class="rfq-grid__cell">Item Descriptions</span>
                            <span class="rfq-grid__cell text-center">Qty</span>
                            <span class="rfq-grid__cell">Satuan</span>
                            @if(auth()->user()->isAdminOrAbove())
                            <span class="rfq-grid__cell text-right">HPP &amp; Margin</span>
                            @endif
                        </div>

                        @if($rfq->type === 'Projek')
                            @php $rowNum = 1; @endphp
                            @forelse(\App\Models\RfqItem::groupProjects($rfq->items) as $group)
                                {{-- Pembatas sub-kategori soft gray (membentang penuh, romawi statis) --}}
                                <div class="rfq-grid__row rfq-grid__group">
                                    <span class="rfq-grid__cell rfq-grid__group-label">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 01-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 011.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 011.414-1.414L17 13.586V12a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                                        {{ $group['label'] }}
                                        <span class="rfq-grid__group-count">({{ $group['items']->count() }} item)</span>
                                    </span>
                                </div>
                                @foreach($group['items'] as $item)
                                <div class="rfq-grid__row {{ $loop->even ? 'rfq-grid__row--zebra' : '' }}">
                                    <span class="rfq-grid__cell rfq-grid__num text-center">{{ $rowNum++ }}</span>
                                    <span class="rfq-grid__cell rfq-grid__desc-block">
                                        <span class="rfq-grid__name">{{ $item->product_name }}</span>
                                        @if($item->detail_item)<span class="rfq-grid__detail">{{ $item->detail_item }}</span>@endif
                                        @if($item->description)<span class="rfq-grid__desc">{{ $item->description }}</span>@endif
                                    </span>
                                    <span class="rfq-grid__cell rfq-grid__num text-center">{{ number_format($item->qty, 0, ',', '.') }}</span>
                                    <span class="rfq-grid__cell"><span class="rfq-grid__unit">{{ $item->unit ?? '-' }}</span></span>
                                    @if(auth()->user()->isAdminOrAbove())
                                    <span class="rfq-grid__cell rfq-grid__status">
                                        @if($item->price_after_margin > 0)
                                            <span class="rfq-grid__hpp">
                                                <span class="rfq-grid__hpp-line">HPP: <b>Rp {{ number_format($item->hpp, 0, ',', '.') }}</b></span>
                                                <span class="rfq-grid__hpp-line">Ongkir: <b>Rp {{ number_format($item->ongkir_pedia + $item->ongkir_pelanggan, 0, ',', '.') }}</b></span>
                                                <span class="rfq-grid__hpp-line">Margin: <b>{{ round((float) $item->margin) }}%</b> / Ceil: {{ $item->ceiling }}</span>
                                                <span class="rfq-grid__hpp-line rfq-grid__hpp-line--jual">Jual: <b>Rp {{ number_format($item->price_after_margin, 0, ',', '.') }}</b></span>
                                            </span>
                                        @else
                                            <span class="rfq-grid__badge">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                                Belum diinput
                                            </span>
                                        @endif
                                    </span>
                                    @endif
                                </div>
                                @endforeach
                            @empty
                                <div class="rfq-grid__row rfq-grid__empty">
                                    <span class="rfq-grid__cell">Belum ada item</span>
                                </div>
                            @endforelse
                        @else
                            @forelse($rfq->items as $item)
                            <div class="rfq-grid__row {{ $loop->even ? 'rfq-grid__row--zebra' : '' }}">
                                <span class="rfq-grid__cell rfq-grid__num text-center">{{ $loop->iteration }}</span>
                                <span class="rfq-grid__cell rfq-grid__desc-block">
                                    <span class="rfq-grid__name">{{ $item->product_name }}</span>
                                    @if($item->description)<span class="rfq-grid__desc">{{ $item->description }}</span>@endif
                                </span>
                                <span class="rfq-grid__cell rfq-grid__num text-center">{{ number_format($item->qty, 0, ',', '.') }}</span>
                                <span class="rfq-grid__cell"><span class="rfq-grid__unit">{{ $item->unit ?? '-' }}</span></span>
                                @if(auth()->user()->isAdminOrAbove())
                                <span class="rfq-grid__cell rfq-grid__status">
                                    @if($item->price_after_margin > 0)
                                        <span class="rfq-grid__hpp">
                                            <span class="rfq-grid__hpp-line">HPP: <b>Rp {{ number_format($item->hpp, 0, ',', '.') }}</b></span>
                                            <span class="rfq-grid__hpp-line">Ongkir: <b>Rp {{ number_format($item->ongkir_pedia + $item->ongkir_pelanggan, 0, ',', '.') }}</b></span>
                                            <span class="rfq-grid__hpp-line">Margin: <b>{{ round((float) $item->margin) }}%</b> / Ceil: {{ $item->ceiling }}</span>
                                            <span class="rfq-grid__hpp-line rfq-grid__hpp-line--jual">Jual: <b>Rp {{ number_format($item->price_after_margin, 0, ',', '.') }}</b></span>
                                        </span>
                                    @else
                                        <span class="rfq-grid__badge">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                            Belum diinput
                                        </span>
                                    @endif
                                </span>
                                @endif
                            </div>
                            @empty
                            <div class="rfq-grid__row rfq-grid__empty">
                                <span class="rfq-grid__cell">Belum ada item</span>
                            </div>
                            @endforelse
                        @endif

                        {{-- Footer total --}}
                        @if(auth()->user()->isAdminOrAbove() && $rfq->items->sum('price_after_margin') > 0)
                        <div class="rfq-grid__row rfq-grid__foot">
                            <span class="rfq-grid__cell">
                                Total Harga Jual
                                <span class="rfq-grid__foot-total">Rp {{ number_format($rfq->items->sum('price_after_margin'), 0, ',', '.') }}</span>
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>


        {{-- Kanan: Summary --}}
        <div class="space-y-5">
            <div class="card p-6 animate-in" style="animation-delay: 0.2s;">
                <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid #e2e8f0;">
                    Ringkasan & Status
                </h2>
                <div class="space-y-3">
                    @php
                        $badgeClass = match($rfq->status) {
                            'Approved', 'Quotation Created' => 'approved',
                            'Cancelled' => 'rejected',
                            default     => 'pending',
                        };
                    @endphp
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm" style="color: var(--text-muted);">Status</span>
                        <span class="status-badge {{ $badgeClass }}">
                            <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                            {{ $rfq->status }}
                        </span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span style="color: var(--text-muted);">Total Item</span>
                        <span style="color: var(--text-secondary);">{{ number_format($rfq->items->sum('qty'), 0, ',', '.') }}</span>
                    </div>
                    <div class="pt-3" style="border-top: 1px solid #e2e8f0;">
                        @if(auth()->user()->isAdminOrAbove() || in_array($rfq->status, [\App\Models\Rfq::STATUS_APPROVED, 'Quotation Created', 'GOAL', 'Quotation Sent']))
                        <div class="flex justify-between text-sm mb-1">
                            <span style="color: var(--text-muted);">Estimasi Harga Jual</span>
                            <span class="font-bold" style="color: var(--text-primary);">
                                Rp {{ number_format($rfq->items->sum('price_after_margin'), 0, ',', '.') }}
                            </span>
                        </div>
                        @endif
                        <div class="mt-3 rounded-lg border px-3 py-2" style="border-color: #e2e8f0; background: rgba(37,99,235,0.04);">
                            <p class="text-[10px] uppercase tracking-wide mb-1" style="color: var(--text-muted);">Apa yang terjadi selanjutnya?</p>
                            <p class="text-sm font-medium" style="color: var(--text-primary);">{{ $rfq->getNextActionLabel() }}</p>
                        </div>
                        <div class="mt-3 rounded-lg border px-3 py-2" style="border-color: #e2e8f0; background: rgba(16,185,129,0.04);">
                            <div class="flex items-center justify-between text-[10px] uppercase tracking-wide" style="color: var(--text-muted);">
                                <span>Progress workflow</span>
                                <span>{{ $rfq->getWorkflowProgressPercent() }}%</span>
                            </div>
                            <div class="mt-2 h-2 rounded-full" style="background: rgba(148,163,184,0.25);">
                                @php
                                    $progressPercent = $rfq->getWorkflowProgressPercent();
                                @endphp
                                <div class="h-2 rounded-full bg-gradient-to-r from-emerald-600 to-teal-600" data-width="{{ $progressPercent }}"></div>
                            </div>
                            <p class="mt-2 text-sm font-medium" style="color: var(--text-primary);">{{ $rfq->getWorkflowStageLabel() }}</p>
                        </div>
                        @if(auth()->user()->isAdminOrAbove() || in_array($rfq->status, [\App\Models\Rfq::STATUS_APPROVED, 'Quotation Created', 'GOAL', 'Quotation Sent']))
                        <p class="text-[10px] mt-2" style="color: var(--text-muted);">* Total keseluruhan setelah dibulatkan</p>
                        @endif
                    </div>
                </div>
            </div>

            @if($rfq->isPendingAdmin() && auth()->user()->isAdminOrAbove())
            <a href="{{ route('rfq.price_form', $rfq) }}"
               class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition-all hover:-translate-y-0.5"
               style="background: linear-gradient(135deg, #059669, #0d9488); box-shadow: 0 4px 14px rgba(5,150,105,0.35);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Isi Harga Modal & Margin
            </a>
            @endif

            @if($rfq->status === \App\Models\Rfq::STATUS_PENDING_LEADER && (auth()->user()->isLeader() || auth()->user()->isSuperAdmin()))
            <div class="flex flex-col gap-2">
                <form action="{{ route('rfq.approve', $rfq) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition-all hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 14px rgba(37,99,235,0.35);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Approve RFQ
                    </button>
                </form>
                <form action="{{ route('rfq.reject', $rfq) }}" method="POST" onsubmit="return confirm('Yakin ingin mengembalikan RFQ ini ke Admin Purchase untuk revisi harga?');">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium rounded-xl transition-colors"
                        style="background: rgba(225,29,72,0.1); color: var(--accent-rose); border: 1px solid rgba(225,29,72,0.2);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Tolak & Kembalikan
                    </button>
                </form>
            </div>
            @endif

            @if(in_array($rfq->status, [\App\Models\Rfq::STATUS_APPROVED, 'Quotation Created', 'GOAL']))
            <div class="flex gap-2 w-full">
                <a href="{{ route('rfq.preview_quotation', $rfq) }}" target="_blank"
                   class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition-all hover:-translate-y-0.5"
                   style="background: linear-gradient(135deg, #3b82f6, #2563eb); box-shadow: 0 4px 14px rgba(59,130,246,0.35);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Web Preview
                </a>
                
                <a href="{{ route('rfq.download_quotation', $rfq) }}"
                   class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition-all hover:-translate-y-0.5"
                   style="background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 14px rgba(245,158,11,0.35);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Download PDF
                </a>
            </div>

            @if(auth()->user()->isSales() || auth()->user()->hasPermission('CRUD'))
            <a href="{{ route('rfq.edit_qty', $rfq) }}"
               class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium rounded-xl transition-colors"
               style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                Revisi QTY
            </a>

            @if(in_array($rfq->status, [\App\Models\Rfq::STATUS_APPROVED, 'Quotation Created']))
            <div class="p-4 rounded-xl border mt-4" style="border-color: #e2e8f0; background: #ffffff;">
                <h3 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Upload PO Customer (Menjadi GOAL)</h3>
                <p class="text-xs mb-3" style="color: var(--text-muted);">Jika klien setuju, silakan upload bukti PO di sini untuk diproses menjadi GOAL.</p>
                <form action="{{ route('rfq.upload_po', $rfq) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="po_file" required accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs mb-3 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border rounded-xl" style="border-color: var(--border-color);">
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition-all hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, #2563eb, #1d4ed8); box-shadow: 0 4px 14px rgba(37,99,235,0.35);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Upload PO
                    </button>
                </form>
            </div>
            @endif

            @endif
            
            @if($rfq->status === \App\Models\Rfq::STATUS_PO_PENDING_ADMIN && auth()->user()->isAdminOrAbove())
            <div class="p-4 rounded-xl border mt-4" style="border-color: #e2e8f0; background: #ffffff;">
                <h3 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Verifikasi PO dari Sales Marketing</h3>
                <p class="text-xs mb-3" style="color: var(--text-muted);">Sales Marketing telah menyesuaikan QTY dan mengunggah bukti PO dari customer. Silakan cek bukti PO dan pastikan QTY sesuai.</p>
                <form action="{{ route('rfq.verify_po', $rfq) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition-all hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 4px 14px rgba(5,150,105,0.35);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Verifikasi & Teruskan ke Leader
                    </button>
                </form>
            </div>
            @endif

            @if($rfq->status === \App\Models\Rfq::STATUS_PO_PENDING_LEADER && (auth()->user()->isLeader() || auth()->user()->isSuperAdmin()))
            <div class="p-4 rounded-xl border mt-4" style="border-color: #e2e8f0; background: #ffffff;">
                <h3 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Persetujuan Akhir (GOAL)</h3>
                <p class="text-xs mb-3" style="color: var(--text-muted);">Admin Purchase telah memverifikasi PO ini. Setujui untuk menandai sebagai GOAL.</p>
                <form action="{{ route('rfq.approve_goal', $rfq) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition-all hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, #2563eb, #1d4ed8); box-shadow: 0 4px 14px rgba(37,99,235,0.35);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Approve Menjadi GOAL
                    </button>
                </form>
            </div>
            @endif
            @endif

            @if(in_array($rfq->status, [\App\Models\Rfq::STATUS_PO_PENDING_ADMIN, \App\Models\Rfq::STATUS_PO_PENDING_LEADER, 'GOAL']) && $rfq->po_file_path)
            <div class="mt-4">
                <a href="{{ Storage::url($rfq->po_file_path) }}" target="_blank"
                   class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium rounded-xl transition-colors"
                   style="background: rgba(16,185,129,0.1); color: var(--accent-emerald); border: 1px solid rgba(16,185,129,0.2);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    Lihat Bukti PO Customer
                </a>
            </div>
            @endif
        </div>
    </div>

</x-app-layout>
