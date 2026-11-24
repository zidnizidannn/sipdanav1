@extends('layouts.app')

@section('title', 'Detail #' . $proposal->code . ' - SIPDANA')
@section('page_title', 'Detail Pengajuan')

@section('content')
    @php
        $totalNominal = $proposal->items->sum(fn($i) => $i->qty * $i->unit_price);
        $isApplicantView = $user->isApplicant();

        $canExamine = $proposal->status === 'WAITING_EXAMINATION' && (
            ($user->role_code === 'CHECKER_ORG' && $proposal->recipient_type === 'PEMBERI_ANGGARAN') ||
            ($user->role_code === 'CHECKER_YAYASAN' && $proposal->recipient_type === 'YAYASAN')
        );

        $canApprove = $proposal->status === 'WAITING_APPROVAL' && (
            ($user->role_code === 'PEMBERI_ANGGARAN' && $proposal->recipient_type === 'PEMBERI_ANGGARAN') ||
            ($user->role_code === 'YAYASAN' && $user->yayasan_mode === 'FUND_PROVIDER' && $proposal->recipient_type === 'YAYASAN')
        );

        $canEscalate = $canApprove
            && $user->role_code === 'YAYASAN'
            && $user->yayasan_mode === 'FUND_PROVIDER'
            && $proposal->recipient_type === 'YAYASAN'
            && is_null($proposal->escalated_to_id);

        $canDisburse = $user->isKu()
            && $proposal->status === 'WAITING_DISBURSEMENT'
            && is_null($proposal->escalated_to_id);

        $canRevise = $proposal->user_id === $user->id && $proposal->status === 'REVISION';

        $statusBadges = [
            'WAITING_EXAMINATION'  => ['label' => 'Menunggu Pemeriksaan', 'cls' => 'bg-amber-50 text-amber-800 border-amber-200', 'dot' => 'bg-amber-500'],
            'REVISION'             => ['label' => 'Revisi',               'cls' => 'bg-rose-50 text-rose-700 border-rose-200',     'dot' => 'bg-rose-500'],
            'WAITING_APPROVAL'     => ['label' => 'Menunggu Persetujuan', 'cls' => 'bg-sky-50 text-sky-800 border-sky-200',        'dot' => 'bg-sky-500'],
            'WAITING_DISBURSEMENT' => ['label' => 'Menunggu Pencairan',   'cls' => 'bg-emerald-50 text-emerald-800 border-emerald-200', 'dot' => 'bg-emerald-500'],
            'COMPLETED'            => ['label' => 'Selesai',              'cls' => 'bg-teal-900 text-white border-teal-900',       'dot' => 'bg-teal-300'],
        ];
        $st = $statusBadges[$proposal->status] ?? ['label' => $proposal->status, 'cls' => 'bg-slate-100 text-slate-700 border-slate-200', 'dot' => 'bg-slate-400'];
    @endphp

    <div class="space-y-5">
        {{-- Top Bar Navigation --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('proposals.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                &larr; Kembali ke Dashboard
            </a>
            @if($canRevise)
                <a href="{{ route('proposals.edit', $proposal) }}"
                   class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition">
                    Perbaiki &amp; Revisi
                </a>
            @endif
        </div>

        {{-- Banner Info Pencairan KU jika COMPLETED --}}
        @if($proposal->status === 'COMPLETED')
            <div class="p-4 rounded-xl bg-teal-900 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-teal-300">Status Pencairan Keuangan (KU)</div>
                    <div class="text-sm font-bold mt-0.5">Selesai &mdash; Dana Sudah Dicairkan / Ditransfer</div>
                    <div class="text-xs text-teal-200 mt-1">Rekening Tujuan: <b>{{ $proposal->disbursement_account }}</b></div>
                    <div class="text-xs text-teal-100 mt-0.5">{{ $proposal->disbursement_note ?: 'Telah dikonfirmasi selesai oleh KU (Keuangan).' }}</div>
                </div>
                <span class="px-3 py-1.5 rounded-lg bg-white/10 text-xs font-semibold shrink-0 self-start sm:self-center">
                    Terverifikasi KU
                </span>
            </div>
        @endif

        {{-- Panel Konfirmasi Pencairan khusus Role KU --}}
        @if($canDisburse)
            <form action="{{ route('proposals.disburse', $proposal) }}" method="POST"
                  class="p-5 rounded-xl bg-white border-2 border-teal-600 space-y-3.5">
                @csrf
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <span class="px-2 py-0.5 rounded bg-teal-100 text-teal-800 text-[10px] font-bold uppercase tracking-wider">Tindakan KU (Keuangan)</span>
                        <h3 class="text-sm font-bold text-slate-900 mt-1">Pengajuan Sudah di-ACC &mdash; Siap Dicairkan / Transfer</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Pengajuan ini telah disetujui final oleh <b>{{ $proposal->recipient_type === 'YAYASAN' ? 'Yayasan' : 'Pemberi Anggaran' }}</b>. Silakan proses transfer ke rekening tujuan lalu tandai selesai.
                        </p>
                        <div class="mt-2 inline-block px-3 py-1.5 rounded-lg bg-teal-50 border border-teal-200 text-xs text-teal-900">
                            Rekening Pencairan: <b class="font-bold">{{ $proposal->disbursement_account }}</b>
                        </div>
                    </div>
                    <div class="text-left sm:text-right shrink-0">
                        <div class="text-[11px] text-slate-400">Nominal Pencairan</div>
                        <div class="text-base font-bold text-teal-700">Rp {{ number_format($totalNominal, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-2.5 pt-1">
                    <input type="text" name="disbursement_note" value="{{ old('disbursement_note') }}" required
                           placeholder="Catatan pencairan / Nomor Referensi Transfer Bank (wajib diisi)..."
                           class="flex-1 px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-teal-600">
                    <button type="submit"
                            class="px-5 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition shrink-0">
                        Tandai Selesai (Sudah Dicairkan / Transfer)
                    </button>
                </div>
            </form>
        @endif

        {{-- Header Info Card --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 pb-4 border-b border-slate-100">
                <div class="space-y-1.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold text-slate-500">#{{ $proposal->code }}</span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-semibold border {{ $st['cls'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $st['dot'] }}"></span>{{ $st['label'] }}
                        </span>
                        @if($proposal->escalated_from_school)
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-800 border border-amber-200">
                                Eskalasi dari {{ $proposal->escalated_from_school }}
                            </span>
                        @endif
                        @if($proposal->escalated_to_id)
                            <a href="{{ route('proposals.show', $proposal->escalated_to_id) }}"
                               class="inline-block px-2 py-0.5 rounded text-[10px] font-medium bg-teal-50 text-teal-800 border border-teal-200 hover:underline">
                                Lihat Pengajuan Eskalasi Pusat &rarr;
                            </a>
                        @endif
                    </div>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900">{{ $proposal->title }}</h2>
                    <p class="text-xs text-slate-500 leading-relaxed max-w-3xl">{{ $proposal->description ?: 'Tidak ada keterangan tambahan.' }}</p>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 lg:text-right shrink-0">
                    <div class="text-[11px] font-medium text-slate-500">Total Anggaran</div>
                    <div class="text-lg sm:text-xl font-bold text-slate-900 mt-0.5">Rp {{ number_format($totalNominal, 0, ',', '.') }}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">{{ $proposal->items->count() }} Item</div>
                </div>
            </div>

            @if(!$isApplicantView)
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 pt-4 text-xs">
                    <div>
                        <span class="text-slate-400 block mb-0.5">Lembaga Pengaju</span>
                        <span class="font-semibold text-slate-800">{{ $proposal->user->name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">Tujuan Pemberi Dana</span>
                        <span class="font-semibold text-slate-800">{{ $proposal->recipient_type === 'YAYASAN' ? 'Yayasan Pendidikan' : 'Pemberi Anggaran' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">Rekening Pencairan</span>
                        <span class="font-semibold text-slate-800">{{ $proposal->disbursement_account ?: '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">Tanggal</span>
                        <span class="font-semibold text-slate-800">{{ \Carbon\Carbon::parse($proposal->submission_date)->format('d M Y') }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">Lampiran</span>
                        @if($proposal->attachment_path)
                            <a href="{{ asset('storage/' . $proposal->attachment_path) }}" target="_blank" class="font-semibold text-teal-700 hover:underline truncate block">
                                {{ basename($proposal->attachment_path) }}
                            </a>
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 text-xs">
                    <div>
                        <span class="text-slate-400 block mb-0.5">Rekening Pencairan</span>
                        <span class="font-semibold text-slate-800">{{ $proposal->disbursement_account ?: '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">Tanggal Pengajuan</span>
                        <span class="font-semibold text-slate-800">{{ \Carbon\Carbon::parse($proposal->submission_date)->format('d M Y') }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-0.5">Lampiran Dokumen</span>
                        @if($proposal->attachment_path)
                            <a href="{{ asset('storage/' . $proposal->attachment_path) }}" target="_blank" class="font-semibold text-teal-700 hover:underline truncate block">
                                {{ basename($proposal->attachment_path) }}
                            </a>
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            {{-- Kolom Kiri (2/3): Rincian Item & Panel Pemeriksaan/Persetujuan --}}
            <div class="lg:col-span-2 space-y-5">
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="font-bold text-xs text-slate-800">Rincian Item &amp; Checklist Pemeriksaan</h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                {{ $canExamine
                                    ? 'Hapus centang (uncheck) pada item yang perlu direvisi dan tuliskan catatannya.'
                                    : 'Status pemeriksaan per item anggaran.' }}
                            </p>
                        </div>

                        @if($canExamine)
                            <div class="flex items-center gap-1.5">
                                <button type="button" onclick="setAllChecks(true)" class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-[11px] font-semibold transition">
                                    Centang Semua
                                </button>
                                <button type="button" onclick="setAllChecks(false)" class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-[11px] font-semibold transition">
                                    Reset
                                </button>
                            </div>
                        @endif
                    </div>

                    @if($canExamine)
                        <form id="examineForm" action="{{ route('proposals.examine', $proposal) }}" method="POST">
                            @csrf
                            <div class="px-5 py-2 bg-slate-900 text-slate-200 text-[11px] font-medium flex items-center justify-between">
                                <span>Pemeriksaan Tahap 1: {{ $user->name }}</span>
                                <span id="checkedCounter">Disetujui: {{ $proposal->items->count() }} / {{ $proposal->items->count() }}</span>
                            </div>

                            <div class="divide-y divide-slate-100">
                                @foreach($proposal->items as $idx => $item)
                                    @php
                                        $subtotal = $item->qty * $item->unit_price;
                                        $oldChecked = old("items.{$item->id}.is_checked", $item->is_checked ? '1' : '0');
                                        $isChecked = filter_var($oldChecked, FILTER_VALIDATE_BOOLEAN);
                                        $oldNote = old("items.{$item->id}.revision_note", $item->revision_note);
                                    @endphp
                                    <div id="itemRow-{{ $item->id }}" class="p-4 transition {{ !$isChecked ? 'bg-rose-50/40' : '' }}">
                                        <div class="flex items-start gap-3.5">
                                            <div class="pt-0.5">
                                                <input type="hidden" name="items[{{ $item->id }}][is_checked]" value="0">
                                                <input type="checkbox"
                                                       name="items[{{ $item->id }}][is_checked]"
                                                       value="1"
                                                       data-item-id="{{ $item->id }}"
                                                       onchange="refreshExamineState()"
                                                       @checked($isChecked)
                                                       class="item-check w-4 h-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500 cursor-pointer">
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2">
                                                    <div>
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <span class="text-xs font-bold text-slate-800">{{ $idx + 1 }}. {{ $item->name }}</span>
                                                            <span id="badge-{{ $item->id }}" class="px-1.5 py-0.5 rounded text-[10px] font-semibold border {{ $isChecked ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-100 text-rose-800 border-rose-200' }}">
                                                                {{ $isChecked ? 'Sesuai' : 'Perlu Revisi' }}
                                                            </span>
                                                            @if($item->priceCap)
                                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-teal-50 text-teal-800 border border-teal-200">
                                                                    Standar Maks Rp {{ number_format($item->priceCap->max_price, 0, ',', '.') }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <p class="text-xs text-slate-500 mt-0.5">{{ $item->spec ?: '-' }}</p>
                                                    </div>
                                                    <div class="sm:text-right shrink-0">
                                                        <div class="text-xs font-bold text-slate-900">Rp {{ number_format($subtotal, 0, ',', '.') }}</div>
                                                        <div class="text-[11px] text-slate-400">{{ $item->qty }} {{ $item->unit }} &times; Rp {{ number_format($item->unit_price, 0, ',', '.') }}</div>
                                                    </div>
                                                </div>

                                                <div id="noteWrap-{{ $item->id }}" class="mt-3 p-3 rounded-lg bg-white border border-rose-200 {{ $isChecked ? 'hidden' : '' }}">
                                                    <label class="block text-[11px] font-semibold text-rose-800 mb-1">Catatan Revisi Item (Wajib):</label>
                                                    <textarea name="items[{{ $item->id }}][revision_note]"
                                                              rows="2"
                                                              oninput="refreshExamineState()"
                                                              placeholder="Tuliskan alasan revisi untuk item ini..."
                                                              class="item-note w-full px-2.5 py-1.5 rounded border border-rose-200 text-xs text-slate-800 focus:outline-none focus:border-rose-500 bg-white">{{ $oldNote }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Panel Submit Checker --}}
                            <div class="p-5 bg-slate-50 border-t border-slate-200 space-y-3.5">
                                <div id="examineHintBox" class="p-3 rounded-lg border text-xs font-medium bg-emerald-50 border-emerald-200 text-emerald-900">
                                    Seluruh item telah dicentang. Pengajuan siap diteruskan ke tahap Persetujuan.
                                </div>

                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <button type="submit" id="btnExamineSubmit"
                                            class="px-5 py-2 rounded-lg text-xs font-semibold transition bg-teal-600 hover:bg-teal-700 text-white">
                                        Setujui &amp; Teruskan ke {{ $proposal->recipient_type === 'YAYASAN' ? 'Yayasan' : 'Pemberi Anggaran' }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        {{-- Tampilan Read-Only Item --}}
                        <div class="divide-y divide-slate-100">
                            @foreach($proposal->items as $idx => $item)
                                @php $subtotal = $item->qty * $item->unit_price; @endphp
                                <div class="p-4 {{ !$item->is_checked ? 'bg-rose-50/40' : '' }}">
                                    <div class="flex items-start gap-3.5">
                                        <div class="pt-0.5">
                                            <input type="checkbox" disabled @checked($item->is_checked)
                                                   class="w-4 h-4 rounded border-slate-300 text-teal-600 cursor-not-allowed">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2">
                                                <div>
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="text-xs font-bold {{ !$item->is_checked ? 'text-rose-900' : 'text-slate-800' }}">
                                                            {{ $idx + 1 }}. {{ $item->name }}
                                                        </span>
                                                        @if($item->is_checked)
                                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Sesuai</span>
                                                        @else
                                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-100 text-rose-800 border border-rose-200">Perlu Revisi</span>
                                                        @endif
                                                        @if($item->priceCap)
                                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-teal-50 text-teal-800 border border-teal-200">
                                                                Standar Maks Rp {{ number_format($item->priceCap->max_price, 0, ',', '.') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <p class="text-xs text-slate-500 mt-0.5">{{ $item->spec ?: '-' }}</p>
                                                </div>
                                                <div class="sm:text-right shrink-0">
                                                    <div class="text-xs font-bold text-slate-900">Rp {{ number_format($subtotal, 0, ',', '.') }}</div>
                                                    <div class="text-[11px] text-slate-400">{{ $item->qty }} {{ $item->unit }} &times; Rp {{ number_format($item->unit_price, 0, ',', '.') }}</div>
                                                </div>
                                            </div>

                                            @if(!$item->is_checked && $item->revision_note)
                                                <div class="mt-3 p-3 rounded-lg bg-white border border-rose-200 text-xs text-rose-900">
                                                    <span class="font-bold block text-[11px] text-rose-800 mb-0.5">Catatan Revisi Item:</span>
                                                    "{{ $item->revision_note }}"
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Panel Persetujuan Akhir (Pemberi Anggaran / Yayasan Mode Pemberi Dana) --}}
                        @if($canApprove)
                            <div class="p-5 bg-slate-50 border-t border-slate-200 space-y-4">
                                <div class="p-3 rounded-lg border bg-emerald-50 border-emerald-200 text-emerald-900 text-xs font-medium">
                                    Seluruh item telah lolos tahap Checker. Anda dapat memberikan persetujuan akhir (ACC) untuk diteruskan ke antrean pencairan KU{{ $canEscalate ? ', atau mengeskalasi pengajuan sekolah ini ke Pemberi Anggaran Pusat' : '' }}.
                                </div>

                                <div class="grid grid-cols-1 {{ $canEscalate ? 'md:grid-cols-2' : '' }} gap-3">
                                    {{-- Form ACC Final --}}
                                    <form action="{{ route('proposals.approve', $proposal) }}" method="POST"
                                          class="p-3.5 rounded-xl bg-white border border-slate-200 space-y-2.5">
                                        @csrf
                                        <div class="text-xs font-bold text-slate-800">Setujui Final (ACC)</div>
                                        <input type="text" name="approval_note" placeholder="Catatan persetujuan (opsional)..."
                                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-teal-600">
                                        <button type="submit"
                                                class="w-full px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition">
                                            Setujui Final (ACC ke KU)
                                        </button>
                                    </form>

                                    {{-- Form Eskalasi Khusus Yayasan --}}
                                    @if($canEscalate)
                                        <form action="{{ route('proposals.escalate', $proposal) }}" method="POST"
                                              class="p-3.5 rounded-xl bg-white border border-slate-200 space-y-2.5">
                                            @csrf
                                            <div class="text-xs font-bold text-slate-800">Eskalasi ke Pemberi Anggaran</div>
                                            <input type="text" name="escalation_note" placeholder="Alasan eskalasi ke Pusat (opsional)..."
                                                   class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-slate-700">
                                            <button type="submit"
                                                    class="w-full px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold transition">
                                                Eskalasi ke Pusat
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 text-xs text-slate-500">
                                Mode baca untuk peran <b>{{ $user->name }}</b>.
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Kolom Kanan (1/3): Skema Alur & Riwayat Status --}}
            <div class="space-y-5">
                @if(!$isApplicantView)
                    <div class="bg-white rounded-xl border border-slate-200 p-4">
                        <h3 class="font-bold text-[11px] uppercase tracking-wider text-slate-400 mb-2.5">Alur Persetujuan &amp; Pencairan</h3>
                        @if($proposal->recipient_type === 'YAYASAN')
                            <div class="flex flex-wrap items-center justify-between gap-1 text-[11px] font-semibold text-slate-700 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                <span>{{ $proposal->applicant_role_code }}</span>
                                <span class="text-slate-400">&rarr;</span>
                                <span>Checker Yayasan</span>
                                <span class="text-slate-400">&rarr;</span>
                                <span>Yayasan</span>
                                <span class="text-slate-400">&rarr;</span>
                                <span class="text-teal-700">KU</span>
                            </div>
                        @else
                            <div class="flex flex-wrap items-center justify-between gap-1 text-[11px] font-semibold text-slate-700 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                <span>{{ $proposal->applicant_role_code }}</span>
                                <span class="text-slate-400">&rarr;</span>
                                <span>Checker Org</span>
                                <span class="text-slate-400">&rarr;</span>
                                <span>Pemberi Anggaran</span>
                                <span class="text-slate-400">&rarr;</span>
                                <span class="text-teal-700">KU</span>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <h3 class="font-bold text-xs text-slate-800 mb-4">Riwayat Status</h3>
                    <div class="relative pl-5 space-y-4 before:content-[''] before:absolute before:left-1.5 before:top-1.5 before:bottom-1.5 before:w-px before:bg-slate-200">
                        @foreach($proposal->histories->sortByDesc('id') as $h)
                            <div class="relative">
                                <span class="absolute -left-[17px] top-1.5 w-2 h-2 rounded-full {{ $loop->first ? 'bg-teal-600' : 'bg-slate-300' }}"></span>
                                <div class="text-xs font-bold text-slate-800">{{ $h->action }}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    {{ $h->actor_label }} &bull; {{ $h->created_at->format('d M Y, H:i') }}
                                </div>
                                @if($h->note)
                                    <div class="mt-1 text-xs text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-100 leading-relaxed">
                                        {{ $h->note }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@if($canExamine)
<script>
    const recipientLabel = @json($proposal->recipient_type === 'YAYASAN' ? 'Yayasan' : 'Pemberi Anggaran');

    function setAllChecks(checked) {
        document.querySelectorAll('.item-check').forEach(cb => {
            cb.checked = checked;
        });
        refreshExamineState();
    }

    function refreshExamineState() {
        const checkboxes = Array.from(document.querySelectorAll('.item-check'));
        const total = checkboxes.length;
        let checkedCount = 0;
        let missingNotes = 0;

        checkboxes.forEach(cb => {
            const id = cb.dataset.itemId;
            const row = document.getElementById(`itemRow-${id}`);
            const badge = document.getElementById(`badge-${id}`);
            const noteWrap = document.getElementById(`noteWrap-${id}`);
            const textarea = noteWrap ? noteWrap.querySelector('textarea') : null;

            if (cb.checked) {
                checkedCount++;
                row.classList.remove('bg-rose-50/40');
                badge.textContent = 'Sesuai';
                badge.className = 'px-1.5 py-0.5 rounded text-[10px] font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200';
                noteWrap.classList.add('hidden');
                if (textarea) textarea.required = false;
            } else {
                row.classList.add('bg-rose-50/40');
                badge.textContent = 'Perlu Revisi';
                badge.className = 'px-1.5 py-0.5 rounded text-[10px] font-semibold border bg-rose-100 text-rose-800 border-rose-200';
                noteWrap.classList.remove('hidden');
                if (textarea) {
                    textarea.required = true;
                    if (textarea.value.trim() === '') missingNotes++;
                }
            }
        });

        document.getElementById('checkedCounter').textContent = `Disetujui: ${checkedCount} / ${total}`;

        const hintBox = document.getElementById('examineHintBox');
        const btnSubmit = document.getElementById('btnExamineSubmit');

        if (checkedCount === total) {
            hintBox.className = 'p-3 rounded-lg border text-xs font-medium bg-emerald-50 border-emerald-200 text-emerald-900';
            hintBox.textContent = `Seluruh ${total} item telah dicentang. Pengajuan siap diteruskan ke ${recipientLabel}.`;
            btnSubmit.disabled = false;
            btnSubmit.className = 'px-5 py-2 rounded-lg text-xs font-semibold transition bg-teal-600 hover:bg-teal-700 text-white cursor-pointer';
            btnSubmit.textContent = `Setujui & Teruskan ke ${recipientLabel}`;
        } else if (missingNotes > 0) {
            hintBox.className = 'p-3 rounded-lg border text-xs font-medium bg-rose-50 border-rose-200 text-rose-900';
            hintBox.textContent = `Mohon isi catatan revisi pada ${missingNotes} item yang tidak dicentang sebelum mengembalikan pengajuan.`;
            btnSubmit.disabled = true;
            btnSubmit.className = 'px-5 py-2 rounded-lg text-xs font-semibold transition bg-slate-200 text-slate-400 cursor-not-allowed';
            btnSubmit.textContent = 'Kembalikan ke Pengaju (Revisi)';
        } else {
            const uncheckedTotal = total - checkedCount;
            hintBox.className = 'p-3 rounded-lg border text-xs font-medium bg-amber-50 border-amber-200 text-amber-900';
            hintBox.textContent = `Terdapat ${uncheckedTotal} item perlu revisi dan catatan sudah lengkap. Klik tombol di bawah untuk mengembalikan ke pengaju.`;
            btnSubmit.disabled = false;
            btnSubmit.className = 'px-5 py-2 rounded-lg text-xs font-semibold transition bg-rose-600 hover:bg-rose-700 text-white cursor-pointer';
            btnSubmit.textContent = 'Kembalikan ke Pengaju (Revisi)';
        }
    }

    refreshExamineState();
</script>
@endif
@endpush