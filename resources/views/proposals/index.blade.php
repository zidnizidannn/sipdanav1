@extends('layouts.app')

@section('title', 'Dashboard - SIPDANA')
@section('page_title', 'Dashboard')

@section('content')
    @php
        $canCreate = $user->isApplicant();
        $showEntityAndTarget = !$canCreate;
        $activeStatus = request('status');

        $totalCount = $stats['total_count'] ?? $proposals->count();
        $totalNominal = $stats['total_nominal'] ?? $proposals->sum(fn($p) => $p->items->sum(fn($i) => $i->qty * $i->unit_price));

        $statusBadges = [
            'WAITING_EXAMINATION'  => ['label' => 'Menunggu Pemeriksaan', 'cls' => 'bg-amber-50 text-amber-800 border-amber-200', 'dot' => 'bg-amber-500'],
            'REVISION'             => ['label' => 'Revisi',               'cls' => 'bg-rose-50 text-rose-700 border-rose-200',     'dot' => 'bg-rose-500'],
            'WAITING_APPROVAL'     => ['label' => 'Menunggu Persetujuan', 'cls' => 'bg-sky-50 text-sky-800 border-sky-200',        'dot' => 'bg-sky-500'],
            'WAITING_DISBURSEMENT' => ['label' => 'Menunggu Pencairan',   'cls' => 'bg-emerald-50 text-emerald-800 border-emerald-200', 'dot' => 'bg-emerald-500'],
            'COMPLETED'            => ['label' => 'Selesai',              'cls' => 'bg-teal-900 text-white border-teal-900',       'dot' => 'bg-teal-300'],
        ];
    @endphp

    {{-- Banner Khusus Yayasan (Dual Role) atau KU --}}
    @if($user->role_code === 'YAYASAN')
        @php $isProvider = $user->yayasan_mode === 'FUND_PROVIDER'; @endphp
        <div class="mb-6 p-4 rounded-xl bg-white border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-bold uppercase tracking-wider">Peran Ganda Yayasan</span>
                    <h2 class="text-sm font-bold text-slate-900">
                        {{ $isProvider ? 'Mode: Yayasan sebagai Pemberi Dana Sekolah' : 'Mode: Yayasan sebagai Pengajuan Dana' }}
                    </h2>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $isProvider
                        ? 'Menyetujui pengajuan anggaran dari 5 Unit Sekolah/Pondok yang telah diperiksa oleh Checker Yayasan.'
                        : 'Mengajukan anggaran Yayasan ke Pemberi Anggaran melalui Checker Organisasi.' }}
                </p>
            </div>
            <form action="{{ route('yayasan.toggle-mode') }}" method="POST" class="shrink-0">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold transition">
                    Ganti ke Mode {{ $isProvider ? 'Pengajuan Dana' : 'Pemberi Dana Sekolah' }}
                </button>
            </form>
        </div>
    @elseif($user->isKu())
        <div class="mb-6 p-4 rounded-xl bg-white border border-slate-200">
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded bg-teal-50 text-teal-800 border border-teal-200 text-[10px] font-bold uppercase tracking-wider">Divisi Keuangan (KU)</span>
                <h2 class="text-sm font-bold text-slate-900">Panel Pencairan &amp; Konfirmasi Transfer Dana</h2>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Menampilkan daftar pengajuan yang sudah di-ACC oleh Pemberi Anggaran maupun Yayasan untuk segera dicairkan/ditransfer dan ditandai <b>Selesai</b>.
            </p>
        </div>
    @endif

    {{-- Header Row --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <p class="text-xs font-medium text-slate-500">{{ $canCreate ? 'Panel Pengajuan Anggaran' : $user->name }}</p>
            <h2 class="text-lg sm:text-xl font-bold text-slate-900 mt-0.5">Ringkasan Anggaran</h2>
        </div>
        <div class="flex items-center gap-2">
            @if($user->isChecker())
                <a href="{{ route('price-caps.index') }}" class="px-4 py-2 rounded-lg bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold transition">
                    Standar Harga
                </a>
            @endif
            @if($canCreate)
                <a href="{{ route('proposals.create') }}" class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition">
                    Buat Pengajuan
                </a>
            @endif
            @if($activeStatus)
                <a href="{{ route('proposals.index') }}" class="px-4 py-2 rounded-lg bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold transition">
                    Tampilkan Semua
                </a>
            @endif
        </div>
    </div>

    {{-- 6 Kartu Ringkasan (Persis Prototipe SIPDANA) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5 mb-8">
        <a href="{{ route('proposals.index', ['status' => $activeStatus === 'WAITING_EXAMINATION' ? null : 'WAITING_EXAMINATION']) }}"
           class="bg-white p-4 rounded-xl border {{ $activeStatus === 'WAITING_EXAMINATION' ? 'border-amber-500 ring-1 ring-amber-400' : 'border-slate-200 hover:border-amber-300' }} transition">
            <div class="text-xs font-medium text-slate-500">Menunggu Pemeriksaan</div>
            <div class="text-2xl font-bold text-amber-600 mt-2">{{ $stats['waiting_examination'] }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Tahap Checker</div>
        </a>

        <a href="{{ route('proposals.index', ['status' => $activeStatus === 'REVISION' ? null : 'REVISION']) }}"
           class="bg-white p-4 rounded-xl border {{ $activeStatus === 'REVISION' ? 'border-rose-500 ring-1 ring-rose-400' : 'border-slate-200 hover:border-rose-300' }} transition">
            <div class="text-xs font-medium text-slate-500">Revisi</div>
            <div class="text-2xl font-bold text-rose-600 mt-2">{{ $stats['revision'] }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Perlu perbaikan</div>
        </a>

        <a href="{{ route('proposals.index', ['status' => $activeStatus === 'WAITING_APPROVAL' ? null : 'WAITING_APPROVAL']) }}"
           class="bg-white p-4 rounded-xl border {{ $activeStatus === 'WAITING_APPROVAL' ? 'border-sky-500 ring-1 ring-sky-400' : 'border-slate-200 hover:border-sky-300' }} transition">
            <div class="text-xs font-medium text-slate-500">Menunggu Persetujuan</div>
            <div class="text-2xl font-bold text-sky-600 mt-2">{{ $stats['waiting_approval'] }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Tahap ACC</div>
        </a>

        <a href="{{ route('proposals.index', ['status' => $activeStatus === 'WAITING_DISBURSEMENT' ? null : 'WAITING_DISBURSEMENT']) }}"
           class="bg-white p-4 rounded-xl border {{ $activeStatus === 'WAITING_DISBURSEMENT' ? 'border-emerald-500 ring-1 ring-emerald-400' : 'border-slate-200 hover:border-emerald-300' }} transition">
            <div class="text-xs font-medium text-slate-500">Menunggu Pencairan</div>
            <div class="text-2xl font-bold text-emerald-600 mt-2">{{ $stats['waiting_disbursement'] }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Diproses KU</div>
        </a>

        <a href="{{ route('proposals.index', ['status' => $activeStatus === 'COMPLETED' ? null : 'COMPLETED']) }}"
           class="bg-white p-4 rounded-xl border {{ $activeStatus === 'COMPLETED' ? 'border-teal-600 ring-1 ring-teal-500' : 'border-slate-200 hover:border-teal-500' }} transition">
            <div class="text-xs font-medium text-slate-500">Selesai</div>
            <div class="text-2xl font-bold text-teal-700 mt-2">{{ $stats['completed'] }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Sudah dicairkan</div>
        </a>

        <div class="bg-slate-900 p-4 rounded-xl text-white col-span-2 sm:col-span-1">
            <div class="text-xs font-medium text-slate-400">Total Nominal ({{ $totalCount }})</div>
            <div class="text-base font-bold tracking-tight truncate mt-2" title="Rp {{ number_format($totalNominal, 0, ',', '.') }}">
                Rp {{ number_format($totalNominal, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">Akumulasi anggaran</div>
        </div>
    </div>

    {{-- Pencarian & Filter Instan --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 mb-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="md:col-span-2 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="searchProposalInput" oninput="filterProposalRows()"
                       placeholder="Cari judul, ID (#PD-...), atau lembaga..."
                       class="w-full pl-9 pr-4 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-teal-600">
            </div>
            <div>
                <select onchange="window.location.href = this.value"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs font-medium text-slate-700 focus:outline-none focus:border-teal-600 bg-white">
                    <option value="{{ route('proposals.index') }}" @selected(!$activeStatus)>Semua Status</option>
                    <option value="{{ route('proposals.index', ['status' => 'WAITING_EXAMINATION']) }}" @selected($activeStatus === 'WAITING_EXAMINATION')>Menunggu Pemeriksaan</option>
                    <option value="{{ route('proposals.index', ['status' => 'REVISION']) }}" @selected($activeStatus === 'REVISION')>Revisi</option>
                    <option value="{{ route('proposals.index', ['status' => 'WAITING_APPROVAL']) }}" @selected($activeStatus === 'WAITING_APPROVAL')>Menunggu Persetujuan</option>
                    <option value="{{ route('proposals.index', ['status' => 'WAITING_DISBURSEMENT']) }}" @selected($activeStatus === 'WAITING_DISBURSEMENT')>Menunggu Pencairan</option>
                    <option value="{{ route('proposals.index', ['status' => 'COMPLETED']) }}" @selected($activeStatus === 'COMPLETED')>Selesai</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Tabel Daftar Pengajuan --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-sm">Daftar Pengajuan ({{ $proposals->count() }})</h3>
            @if($activeStatus)
                <a href="{{ route('proposals.index') }}" class="text-xs font-semibold text-rose-600 hover:underline">Reset Filter</a>
            @endif
        </div>

        @if($proposals->isEmpty())
            <div class="p-10 text-center">
                <h4 class="font-semibold text-slate-700 text-sm">Belum Ada Data Pengajuan</h4>
                <p class="text-xs text-slate-400 mt-1">Tidak ditemukan pengajuan dana yang sesuai dengan peran atau filter saat ini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold text-slate-500">
                            <th class="py-3 px-4">ID &amp; Tanggal</th>
                            <th class="py-3 px-4">Judul Pengajuan</th>
                            @if($showEntityAndTarget)
                                <th class="py-3 px-4">Lembaga Pengaju &amp; Tujuan</th>
                            @endif
                            <th class="py-3 px-4 text-right">Total Anggaran</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="proposalTableBody" class="divide-y divide-slate-100 text-xs">
                        @foreach($proposals as $p)
                            @php
                                $total = $p->items->sum(fn($i) => $i->qty * $i->unit_price);
                                $uncheckCount = $p->items->where('is_checked', false)->count();
                                $st = $statusBadges[$p->status] ?? ['label' => $p->status, 'cls' => 'bg-slate-100 text-slate-700 border-slate-200', 'dot' => 'bg-slate-400'];

                                $isInspectable = ($user->isChecker() && $p->status === 'WAITING_EXAMINATION')
                                    || ($user->role_code === 'PEMBERI_ANGGARAN' && in_array($p->status, ['WAITING_EXAMINATION', 'WAITING_APPROVAL']))
                                    || ($user->role_code === 'YAYASAN' && $user->yayasan_mode === 'FUND_PROVIDER' && $p->status === 'WAITING_APPROVAL');
                                $isDisbursable = $user->isKu() && $p->status === 'WAITING_DISBURSEMENT' && !$p->escalated_to_id;
                                $isRevisable = $p->user_id === $user->id && $p->status === 'REVISION';
                            @endphp
                            <tr class="proposal-row hover:bg-slate-50/80 transition"
                                data-search="{{ strtolower($p->code . ' ' . $p->title . ' ' . $p->user->name) }}">
                                <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                    <div class="font-bold text-slate-900">#{{ $p->code }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">{{ \Carbon\Carbon::parse($p->submission_date)->format('Y-m-d') }}</div>
                                </td>
                                <td class="py-3.5 px-4 align-top max-w-xs">
                                    <a href="{{ route('proposals.show', $p) }}" class="font-semibold text-slate-800 hover:text-teal-700 line-clamp-2 leading-snug">
                                        {{ $p->title }}
                                    </a>
                                    <div class="text-[11px] text-slate-400 mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                        <span>{{ $p->items->count() }} Item</span>
                                        @if($p->disbursement_account)
                                            <span>&bull; Rek: <b class="text-slate-600 font-medium">{{ $p->disbursement_account }}</b></span>
                                        @endif
                                        @if($uncheckCount > 0)
                                            <span class="text-rose-600 font-semibold">&bull; {{ $uncheckCount }} Perlu Revisi</span>
                                        @endif
                                    </div>
                                </td>
                                @if($showEntityAndTarget)
                                    <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                        <div class="font-semibold text-slate-800">{{ $p->user->name }}</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">
                                            Tujuan: {{ $p->recipient_type === 'YAYASAN' ? 'Yayasan' : 'Pemberi Anggaran' }}
                                        </div>
                                    </td>
                                @endif
                                <td class="py-3.5 px-4 align-top text-right whitespace-nowrap font-bold text-slate-900">
                                    Rp {{ number_format($total, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 align-top">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-semibold border {{ $st['cls'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $st['dot'] }}"></span>{{ $st['label'] }}
                                    </span>
                                    @if($p->escalated_from_school)
                                        <div class="mt-1">
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-800 border border-amber-200">
                                                Dari {{ $p->escalated_from_school }}
                                            </span>
                                        </div>
                                    @endif
                                    @if($p->escalated_to_id)
                                        <div class="mt-1">
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-medium bg-teal-50 text-teal-800 border border-teal-200">
                                                Dieskalasi ke Pusat
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 align-top text-right whitespace-nowrap">
                                    @if($isDisbursable)
                                        <a href="{{ route('proposals.show', $p) }}" class="inline-block px-3 py-1.5 rounded-lg bg-teal-700 hover:bg-teal-800 text-white font-semibold transition">
                                            Cairkan / Selesai
                                        </a>
                                    @elseif($isInspectable)
                                        <a href="{{ route('proposals.show', $p) }}" class="inline-block px-3 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold transition">
                                            Periksa
                                        </a>
                                    @elseif($isRevisable)
                                        <a href="{{ route('proposals.edit', $p) }}" class="inline-block px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-semibold transition">
                                            Revisi ({{ $uncheckCount }})
                                        </a>
                                    @else
                                        <a href="{{ route('proposals.show', $p) }}" class="inline-block px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold transition">
                                            Detail
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    function filterProposalRows() {
        const q = (document.getElementById('searchProposalInput')?.value || '').toLowerCase().trim();
        document.querySelectorAll('.proposal-row').forEach(row => {
            const text = row.getAttribute('data-search') || '';
            row.style.display = text.includes(q) ? '' : 'none';
        });
    }
</script>
@endpush