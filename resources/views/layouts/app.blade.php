<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SIPDANA - Sistem Pengajuan Dana')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        teal: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
        }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        .fade-in { animation: fadeIn 0.15s ease-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(2px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (min-width: 1024px) {
            #sidebar.sidebar-collapsed { width: 4.5rem; }
            #sidebar.sidebar-collapsed .sidebar-hide-collapsed { display: none !important; }
            #sidebar.sidebar-collapsed .sidebar-center-collapsed {
                justify-content: center !important;
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }
            #sidebar.sidebar-collapsed #sidebarBrandHeader {
                justify-content: center !important;
                padding: 1rem 0.5rem !important;
            }
        }
    </style>
</head>
<body class="text-slate-800 antialiased selection:bg-teal-600 selection:text-white">

@php
    $authUser = auth()->user();
    $isLocal = app()->environment('local');
    $devUsers = $isLocal ? \App\Models\User::orderBy('id')->get() : collect();

    $initial = $authUser ? strtoupper(substr($authUser->name, 0, 1)) : 'S';
    $roleSubLabel = 'Pemeriksa / Otoritas';
    if ($authUser) {
        if ($authUser->isKu()) {
            $roleSubLabel = 'Pencairan & Transfer Dana';
        } elseif ($authUser->isApplicant()) {
            $roleSubLabel = 'Pengaju Anggaran';
        }
    }

    // Hitung notifikasi antrean real-time dari database untuk peran aktif
    $notifItems = collect();
    if ($authUser) {
        $notifQuery = \App\Models\Proposal::with(['user', 'items'])->latest();
        if ($authUser->role_code === 'CHECKER_ORG') {
            $notifItems = $notifQuery->where('recipient_type', 'PEMBERI_ANGGARAN')->where('status', 'WAITING_EXAMINATION')->get();
        } elseif ($authUser->role_code === 'CHECKER_YAYASAN') {
            $notifItems = $notifQuery->where('recipient_type', 'YAYASAN')->where('status', 'WAITING_EXAMINATION')->get();
        } elseif ($authUser->role_code === 'PEMBERI_ANGGARAN') {
            $notifItems = $notifQuery->where('recipient_type', 'PEMBERI_ANGGARAN')->where('status', 'WAITING_APPROVAL')->get();
        } elseif ($authUser->role_code === 'YAYASAN' && $authUser->yayasan_mode === 'FUND_PROVIDER') {
            $notifItems = $notifQuery->where('recipient_type', 'YAYASAN')->where('status', 'WAITING_APPROVAL')->get();
        } elseif ($authUser->isKu()) {
            $notifItems = $notifQuery->where('status', 'WAITING_DISBURSEMENT')->whereNull('escalated_to_id')->get();
        } else {
            $notifItems = $notifQuery->where('user_id', $authUser->id)->where('status', 'REVISION')->get();
        }
    }

    // Jumlah badge khusus Yayasan (Dual Mode)
    $yayasanSchoolWaiting = 0;
    $yayasanOwnCount = 0;
    if ($authUser && $authUser->role_code === 'YAYASAN') {
        $yayasanSchoolWaiting = \App\Models\Proposal::where('recipient_type', 'YAYASAN')->where('status', 'WAITING_APPROVAL')->count();
        $yayasanOwnCount = \App\Models\Proposal::where('user_id', $authUser->id)->count();
    }
@endphp

    {{-- ================= MODAL PILIH PERAN (KHUSUS LOCAL DEV) ================= --}}
    @if($isLocal && $devUsers->isNotEmpty())
        <div id="roleModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] z-50 hidden flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-lg max-w-2xl w-full overflow-hidden border border-slate-200 fade-in max-h-[90vh] flex flex-col">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-white">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Pilih Peran Simulasi Pengguna</h2>
                        <p class="text-slate-500 text-xs mt-0.5">Beralih antar 16 peran untuk menguji skenario pengajuan hingga pencairan dana</p>
                    </div>
                    <button type="button" onclick="closeRoleModal()" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700 transition">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto space-y-5">
                    @php
                        $groupApprovers = $devUsers->whereIn('role_code', ['CHECKER_ORG', 'CHECKER_YAYASAN', 'PEMBERI_ANGGARAN', 'KU']);
                        $yayasanUser = $devUsers->firstWhere('role_code', 'YAYASAN');
                        $groupMain = $devUsers->where('entity_group', 'MAIN_ENTITY')->where('role_code', '!=', 'YAYASAN');
                        $groupSchool = $devUsers->where('entity_group', 'SCHOOL');
                    @endphp

                    <div>
                        <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Pemeriksa, Persetujuan &amp; Keuangan (KU)</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            @foreach($groupApprovers as $u)
                                <form action="{{ route('dev.switch-role') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $u->id }}">
                                    <button type="submit" class="w-full p-3 rounded-lg border text-left transition {{ $authUser && $authUser->id === $u->id ? 'bg-teal-50/70 border-teal-600 text-teal-950' : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50' }}">
                                        <div class="font-bold text-xs text-slate-900 truncate">{{ $u->name }}</div>
                                        <div class="text-[11px] text-slate-500 truncate mt-0.5">{{ $u->role_code }}</div>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>

                    @if($yayasanUser)
                        <div>
                            <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Lembaga Khusus (Peran Ganda)</h3>
                            <form action="{{ route('dev.switch-role') }}" method="POST">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $yayasanUser->id }}">
                                <button type="submit" class="w-full p-3.5 rounded-lg border text-left transition {{ $authUser && $authUser->id === $yayasanUser->id ? 'bg-amber-50/60 border-amber-400' : 'bg-slate-50 border-slate-200 hover:bg-slate-100' }}">
                                    <div class="font-bold text-xs text-slate-900">{{ $yayasanUser->name }} (Peran Ganda)</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">Pemberi Dana untuk 5 Sekolah/Pondok atau Pengajuan Dana ke Pusat.</div>
                                </button>
                            </form>
                        </div>
                    @endif

                    <div>
                        <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Kelompok 1: Bagian Organisasi &amp; Tim (Ke Checker Org &rarr; Pemberi Anggaran &rarr; KU)</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                            @foreach($groupMain as $u)
                                <form action="{{ route('dev.switch-role') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $u->id }}">
                                    <button type="submit" class="w-full p-3 rounded-lg border text-left transition {{ $authUser && $authUser->id === $u->id ? 'bg-teal-50/70 border-teal-600 text-teal-950' : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50' }}">
                                        <div class="font-bold text-xs text-slate-900 truncate">{{ $u->name }}</div>
                                        <div class="text-[11px] text-slate-500 truncate mt-0.5">{{ $u->role_code }}</div>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Kelompok 2: Bagian Yayasan / Sekolah (Ke Checker Yayasan &rarr; Yayasan &rarr; KU)</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5">
                            @foreach($groupSchool as $u)
                                <form action="{{ route('dev.switch-role') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $u->id }}">
                                    <button type="submit" class="w-full p-3 rounded-lg border text-left transition {{ $authUser && $authUser->id === $u->id ? 'bg-teal-50/70 border-teal-600 text-teal-950' : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50' }}">
                                        <div class="font-bold text-xs text-slate-900 truncate">{{ $u->name }}</div>
                                        <div class="text-[11px] text-slate-500 truncate mt-0.5">{{ $u->role_code }}</div>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-between items-center text-xs text-slate-500">
                    <span>Anda juga dapat mengganti peran langsung dari bar atas.</span>
                    <button type="button" onclick="closeRoleModal()" class="px-4 py-1.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold rounded-lg transition">Tutup</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ================= MODAL PANDUAN ALUR ================= --}}
    <div id="guideModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-lg max-w-3xl w-full overflow-hidden border border-slate-200 fade-in max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-base font-bold text-slate-900">Panduan Alur Kerja SIPDANA</h2>
                <button type="button" onclick="toggleGuideModal()" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-6 overflow-y-auto space-y-5 text-sm text-slate-600 leading-relaxed">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="font-bold text-slate-900 text-sm mb-1">1. Alur Bagian Organisasi</div>
                        <p class="text-xs text-slate-500 mb-2.5">(Persinas, LDII, Forsgi, Senkom, Tim Pembangunan, PPG, Yayasan [Mode Pengaju])</p>
                        <ol class="list-decimal list-inside space-y-1.5 text-xs text-slate-700">
                            <li>Organisasi membuat pengajuan &rarr; <b>Menunggu Pemeriksaan</b>.</li>
                            <li><b>Checker Organisasi</b> memeriksa item dengan checkbox:
                                <ul class="list-disc list-inside ml-4 mt-1 text-slate-600">
                                    <li>Ada item tidak dicentang &rarr; isi catatan &rarr; status <b>Revisi</b>.</li>
                                    <li>Semua item dicentang &rarr; status <b>Menunggu Persetujuan</b>.</li>
                                </ul>
                            </li>
                            <li><b>Pemberi Anggaran</b> klik <b>Setujui Final (ACC)</b> &rarr; status <b>Menunggu Pencairan</b>.</li>
                            <li><b>KU (Keuangan)</b> mencairkan dana &rarr; klik <b>Tandai Selesai</b> &rarr; status <b>Selesai</b>.</li>
                        </ol>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="font-bold text-slate-900 text-sm mb-1">2. Alur Bagian Yayasan (Sekolah &amp; Pondok)</div>
                        <p class="text-xs text-slate-500 mb-2.5">(RA, SDIT, Wustho, Ulya, Pondok &rarr; Checker Yayasan &rarr; Yayasan)</p>
                        <ol class="list-decimal list-inside space-y-1.5 text-xs text-slate-700">
                            <li>Sekolah/Pondok membuat pengajuan &rarr; <b>Menunggu Pemeriksaan</b>.</li>
                            <li><b>Checker Yayasan</b> memeriksa item pengajuan sekolah:
                                <ul class="list-disc list-inside ml-4 mt-1 text-slate-600">
                                    <li>Item perlu revisi &rarr; uncheck + catatan &rarr; status <b>Revisi</b>.</li>
                                    <li>Semua item dicentang &rarr; status <b>Menunggu Persetujuan</b>.</li>
                                </ul>
                            </li>
                            <li><b>Yayasan (Mode Pemberi Dana)</b> memilih <b>Setujui Final (ACC)</b> ke KU, atau <b>Eskalasi ke Pemberi Anggaran</b>.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= MAIN LAYOUT ================= --}}
    <div class="min-h-screen flex flex-col lg:flex-row">
        <div id="sidebarBackdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-900/40 z-30 hidden lg:hidden"></div>

        {{-- SIDEBAR --}}
        <aside id="sidebar" class="fixed lg:sticky top-0 left-0 z-40 w-64 h-screen bg-white text-slate-700 flex flex-col transition-all duration-200 -translate-x-full lg:translate-x-0 shrink-0 border-r border-slate-200">
            {{-- Brand Header --}}
            <div id="sidebarBrandHeader" class="h-16 px-5 border-b border-slate-200 flex items-center justify-between gap-2">
                <a href="{{ route('proposals.index') }}" class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-teal-600 flex items-center justify-center text-white font-bold text-sm shrink-0">
                        S
                    </div>
                    <div class="sidebar-hide-collapsed min-w-0">
                        <div class="font-bold text-slate-900 tracking-tight text-base leading-none">SIPDANA</div>
                        <p class="text-[11px] text-slate-400 mt-0.5 truncate">Pengajuan Dana</p>
                    </div>
                </a>
                <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden text-slate-400 hover:text-slate-700">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            {{-- Active Role Card --}}
            @if($authUser)
                <div class="p-3.5 border-b border-slate-200">
                    <div class="flex items-center justify-between mb-1.5 sidebar-hide-collapsed">
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Peran Aktif</span>
                        @if($isLocal)
                            <button type="button" onclick="openRoleModal()" class="text-[11px] text-teal-600 hover:text-teal-700 font-semibold">
                                Ubah
                            </button>
                        @endif
                    </div>

                    <div @if($isLocal) onclick="openRoleModal()" @endif
                         class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 {{ $isLocal ? 'cursor-pointer hover:border-slate-300' : '' }} transition">
                        <div class="flex items-center gap-2.5 sidebar-center-collapsed" title="Peran Aktif: {{ $authUser->name }}">
                            <div class="w-7 h-7 rounded-md bg-slate-200 text-slate-700 flex items-center justify-center text-xs font-bold shrink-0">
                                {{ $initial }}
                            </div>
                            <div class="min-w-0 sidebar-hide-collapsed">
                                <div class="font-bold text-xs text-slate-900 truncate">{{ $authUser->name }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ $roleSubLabel }}</div>
                                @if($authUser->role_code === 'YAYASAN')
                                    <span class="mt-1 inline-block px-2 py-0.5 rounded text-[10px] font-semibold {{ $authUser->yayasan_mode === 'FUND_PROVIDER' ? 'bg-amber-100 text-amber-800' : 'bg-teal-100 text-teal-800' }}">
                                        {{ $authUser->yayasan_mode === 'FUND_PROVIDER' ? 'Pemberi Dana Sekolah' : 'Pengajuan Dana' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Special Dual Role Switcher for YAYASAN --}}
                    @if($authUser->role_code === 'YAYASAN')
                        @php $isProvider = $authUser->yayasan_mode === 'FUND_PROVIDER'; @endphp
                        <div class="mt-2.5">
                            <div class="p-1.5 rounded-lg bg-slate-100 border border-slate-200">
                                <div class="text-[10px] font-semibold text-slate-500 px-1.5 py-1 sidebar-hide-collapsed">
                                    Mode Peran Yayasan
                                </div>
                                <form action="{{ route('yayasan.toggle-mode') }}" method="POST" class="grid grid-cols-1 gap-1">
                                    @csrf
                                    <button type="{{ $isProvider ? 'button' : 'submit' }}"
                                            class="w-full py-1.5 px-2.5 rounded-md text-xs font-semibold flex items-center justify-between transition sidebar-center-collapsed {{ $isProvider ? 'bg-white text-slate-900 shadow-sm cursor-default' : 'text-slate-600 hover:text-slate-900' }}">
                                        <span class="sidebar-hide-collapsed">Pemberi Dana Sekolah</span>
                                        <span class="px-1.5 py-0.2 rounded text-[10px] sidebar-hide-collapsed">{{ $yayasanSchoolWaiting }}</span>
                                    </button>
                                    <button type="{{ !$isProvider ? 'button' : 'submit' }}"
                                            class="w-full py-1.5 px-2.5 rounded-md text-xs font-semibold flex items-center justify-between transition sidebar-center-collapsed {{ !$isProvider ? 'bg-white text-slate-900 shadow-sm cursor-default' : 'text-slate-600 hover:text-slate-900' }}">
                                        <span class="sidebar-hide-collapsed">Pengajuan Dana</span>
                                        <span class="px-1.5 py-0.2 rounded text-[10px] sidebar-hide-collapsed">{{ $yayasanOwnCount }}</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Navigation Menu --}}
                <nav class="p-3 space-y-1 flex-1 overflow-y-auto">
                    <a href="{{ route('proposals.index') }}" title="Dashboard"
                       class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition sidebar-center-collapsed {{ request()->routeIs('proposals.index') && !request('view') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <span class="flex items-center gap-3">
                            <i class="fa-solid fa-border-all w-4 text-center text-xs {{ request()->routeIs('proposals.index') && !request('view') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                            <span class="sidebar-hide-collapsed">Dashboard</span>
                        </span>
                        @if($notifItems->count() > 0)
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold sidebar-hide-collapsed {{ request()->routeIs('proposals.index') ? 'bg-teal-600 text-white' : 'bg-rose-100 text-rose-700' }}">
                                {{ $notifItems->count() }}
                            </span>
                        @endif
                    </a>

                    @if($authUser->isApplicant())
                        <a href="{{ route('proposals.create') }}" title="Buat Pengajuan"
                           class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition sidebar-center-collapsed {{ request()->routeIs('proposals.create') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-plus w-4 text-center text-xs {{ request()->routeIs('proposals.create') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                                <span class="sidebar-hide-collapsed">Buat Pengajuan</span>
                            </span>
                        </a>
                    @endif

                    @if($authUser->isChecker())
                        <a href="{{ route('price-caps.index') }}" title="Standar Harga"
                           class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition sidebar-center-collapsed {{ request()->routeIs('price-caps.*') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-tags w-4 text-center text-xs {{ request()->routeIs('price-caps.*') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                                <span class="sidebar-hide-collapsed">Standar Harga</span>
                            </span>
                        </a>
                    @endif
                </nav>

                {{-- Sidebar Footer --}}
                <div class="p-3 border-t border-slate-200 space-y-1.5">
                    <button type="button" onclick="toggleGuideModal()" title="Panduan Alur"
                            class="w-full py-2 px-3 rounded-lg hover:bg-slate-100 text-slate-600 text-xs font-medium flex items-center justify-center gap-2 transition border border-slate-200 sidebar-center-collapsed">
                        <i class="fa-regular fa-circle-question text-slate-400"></i>
                        <span class="sidebar-hide-collapsed">Panduan Alur</span>
                    </button>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" title="Keluar"
                                class="w-full py-2 px-3 rounded-lg hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200 text-slate-600 text-xs font-medium flex items-center justify-center gap-2 transition border border-slate-200 sidebar-center-collapsed">
                            <i class="fa-solid fa-right-from-bracket text-slate-400"></i>
                            <span class="sidebar-hide-collapsed">Keluar</span>
                        </button>
                    </form>
                </div>
            @endif
        </aside>

        {{-- MAIN CONTENT WRAPPER --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- TOP NAVBAR --}}
            <header class="sticky top-0 z-20 h-16 bg-white/95 backdrop-blur-sm border-b border-slate-200 px-4 lg:px-8 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden w-9 h-9 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-600">
                        <i class="fa-solid fa-bars text-sm"></i>
                    </button>
                    <button type="button" onclick="toggleDesktopSidebar()" title="Kecilkan / Perbesar Sidebar" class="hidden lg:flex w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-500 items-center justify-center transition">
                        <i class="fa-solid fa-bars text-sm"></i>
                    </button>
                    <h1 class="text-sm sm:text-base font-bold text-slate-900 leading-tight">@yield('page_title', 'Dashboard')</h1>
                </div>

                @if($authUser)
                    <div class="flex items-center gap-2 sm:gap-2.5">
                        {{-- Yayasan Mode Switcher di Header --}}
                        @if($authUser->role_code === 'YAYASAN')
                            @php $isProvider = $authUser->yayasan_mode === 'FUND_PROVIDER'; @endphp
                            <form action="{{ route('yayasan.toggle-mode') }}" method="POST" class="hidden md:flex items-center bg-slate-100 border border-slate-200 rounded-lg p-0.5">
                                @csrf
                                <button type="{{ $isProvider ? 'button' : 'submit' }}" class="px-2.5 py-1 rounded-md text-xs font-semibold transition {{ $isProvider ? 'bg-white text-slate-900 shadow-sm cursor-default' : 'text-slate-600 hover:text-slate-900' }}">
                                    Pemberi Dana (Sekolah)
                                </button>
                                <button type="{{ !$isProvider ? 'button' : 'submit' }}" class="px-2.5 py-1 rounded-md text-xs font-semibold transition {{ !$isProvider ? 'bg-white text-slate-900 shadow-sm cursor-default' : 'text-slate-600 hover:text-slate-900' }}">
                                    Pengajuan Dana
                                </button>
                            </form>
                        @endif

                        {{-- Quick Role Switcher Dropdown (Local Dev) --}}
                        @if($isLocal && $devUsers->isNotEmpty())
                            <form action="{{ route('dev.switch-role') }}" method="POST" class="flex items-center bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg px-2.5 py-1.5 gap-1.5 transition">
                                @csrf
                                <span class="text-[11px] font-medium text-slate-500 hidden md:inline">Peran:</span>
                                <select name="user_id" onchange="this.form.submit()" class="bg-transparent text-xs font-semibold text-slate-800 focus:outline-none cursor-pointer">
                                    @foreach($devUsers as $u)
                                        <option value="{{ $u->id }}" @selected($u->id === $authUser->id)>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @endif

                        {{-- Notification Bell --}}
                        <div class="relative" id="notifDropdownWrapper">
                            <button type="button" onclick="toggleNotifDropdown()" class="w-9 h-9 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-600 relative transition">
                                <i class="fa-regular fa-bell text-sm"></i>
                                @if($notifItems->count() > 0)
                                    <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-600 text-white text-[10px] font-bold flex items-center justify-center">
                                        {{ $notifItems->count() }}
                                    </span>
                                @endif
                            </button>

                            <div id="notifDropdownPanel" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden z-50 fade-in">
                                <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                                    <span class="font-bold text-xs text-slate-800">Notifikasi Antrean</span>
                                    <span class="text-[11px] font-medium px-2 py-0.5 bg-slate-200 text-slate-700 rounded-md">{{ $authUser->name }}</span>
                                </div>
                                <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                                    @forelse($notifItems as $nf)
                                        @php $nfTotal = $nf->items->sum(fn($i) => $i->qty * $i->unit_price); @endphp
                                        <a href="{{ route('proposals.show', $nf) }}" class="block p-3.5 hover:bg-slate-50 transition">
                                            <div class="text-xs font-bold text-slate-800">#{{ $nf->code }} — {{ $nf->user->name }}</div>
                                            <div class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">{{ $nf->title }} (Rp {{ number_format($nfTotal, 0, ',', '.') }})</div>
                                        </a>
                                    @empty
                                        <div class="p-6 text-center text-xs text-slate-400">
                                            Tidak ada antrean tindakan mendesak.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </header>

            {{-- DYNAMIC CONTENT --}}
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-6xl w-full mx-auto">
                @if(session('success'))
                    <div class="mb-5 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-xs font-medium text-teal-900 fade-in">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-medium text-rose-800 fade-in">
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-800 fade-in">
                        <div class="font-bold mb-1">Periksa kembali input Anda:</div>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>

            {{-- FOOTER --}}
            <footer class="px-6 py-4 border-t border-slate-200 bg-white text-xs text-slate-400 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div>&copy; {{ date('Y') }} SIPDANA &mdash; Sistem Pengajuan Dana</div>
                <div class="flex flex-wrap items-center gap-3 text-[11px]">
                    <span>Alur Organisasi: Pengaju &rarr; Checker Org &rarr; Pemberi Anggaran &rarr; KU</span>
                    <span>&bull;</span>
                    <span>Alur Yayasan: Sekolah/Pondok &rarr; Checker Yayasan &rarr; Yayasan &rarr; KU</span>
                </div>
            </footer>
        </div>
    </div>

    <script>
        function openRoleModal() {
            const m = document.getElementById('roleModal');
            if (m) m.classList.remove('hidden');
        }
        function closeRoleModal() {
            const m = document.getElementById('roleModal');
            if (m) m.classList.add('hidden');
        }
        function toggleGuideModal() {
            const m = document.getElementById('guideModal');
            if (m) m.classList.toggle('hidden');
        }
        function toggleMobileSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('sidebarBackdrop').classList.toggle('hidden');
        }
        function toggleDesktopSidebar() {
            document.getElementById('sidebar').classList.toggle('sidebar-collapsed');
        }
        function toggleNotifDropdown() {
            document.getElementById('notifDropdownPanel').classList.toggle('hidden');
        }
        document.addEventListener('click', (e) => {
            const wrapper = document.getElementById('notifDropdownWrapper');
            const panel = document.getElementById('notifDropdownPanel');
            if (wrapper && panel && !wrapper.contains(e.target)) {
                panel.classList.add('hidden');
            }
        });
    </script>

    @stack('scripts')
</body>
</html>