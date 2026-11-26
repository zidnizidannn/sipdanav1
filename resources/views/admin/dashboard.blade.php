<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — SIPDANA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen antialiased">

    {{-- Topbar Khusus Admin --}}
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-teal-600 text-white flex items-center justify-center font-bold text-sm">
                    SD
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-900 text-sm">SIPDANA</span>
                        <span class="px-2 py-0.5 rounded bg-slate-900 text-white text-[10px] font-semibold uppercase">Admin</span>
                    </div>
                    <p class="text-[11px] text-slate-500">Dashboard Admin &amp; Standar Harga Maksimal</p>
                </div>
            </div>

            <nav class="flex items-center gap-1">
                <a href="{{ route('admin.dashboard') }}"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-teal-50 text-teal-700 border border-teal-200' : 'text-slate-600 hover:bg-slate-100' }}">
                    Standar Harga
                </a>
                <a href="{{ route('admin.users.index') }}"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.users.*') ? 'bg-teal-50 text-teal-700 border border-teal-200' : 'text-slate-600 hover:bg-slate-100' }}">
                    Kelola Akun
                </a>
            </nav>

            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <div class="text-xs font-bold text-slate-800">{{ auth()->user()->name }}</div>
                    <div class="text-[11px] text-slate-400">{{ auth()->user()->username }}</div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="px-3.5 py-2 rounded-lg border border-slate-200 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 text-xs font-semibold text-slate-600 transition">
                        <i class="fa-solid fa-right-from-bracket mr-1"></i> Keluar
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-6 space-y-5">

        {{-- Notifikasi Flash & Error Validasi --}}
        @if(session('success'))
            <div class="px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center justify-between">
                <span><i class="fa-solid fa-circle-check mr-1.5"></i>{{ session('success') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 text-base leading-none">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div class="px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium flex items-center justify-between">
                <span><i class="fa-solid fa-circle-exclamation mr-1.5"></i>{{ session('error') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-700 text-base leading-none">&times;</button>
            </div>
        @endif

        @if($errors->any())
            <div class="px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
                <div class="font-bold">Gagal menyimpan data:</div>
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Kartu Ringkasan --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase">Item Batas Harga Aktif</p>
                <p class="text-2xl font-bold text-teal-700 mt-1">
                    {{ $activeCapsCount }} <span class="text-xs font-normal text-slate-400">/ {{ $totalCapsCount }} item</span>
                </p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase">Total Akun Sistem</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ $totalUsers }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase">Total Pengajuan</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ $totalProposals }}</p>
            </div>
        </div>

        {{-- Bar Pencarian & Tombol Tambah --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2 flex-1">
                <input type="text" name="q" value="{{ $search }}"
                       placeholder="Cari nama item, satuan, atau scope..."
                       class="w-full sm:w-72 px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-teal-600">
                <button type="submit" class="px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold transition">
                    Cari
                </button>
                @if($search !== '')
                    <a href="{{ route('admin.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-800 px-2">Reset</a>
                @endif
            </form>

            <button type="button" onclick="openCreateCapModal()"
                    class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition shrink-0">
                + Tambah Batas Harga
            </button>
        </div>

        {{-- Tabel Standar Harga Maksimal --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200">
                <h2 class="text-sm font-bold text-slate-800">Daftar Standar Harga Maksimal</h2>
                <p class="text-[11px] text-slate-500">Hanya Admin yang dapat menambah, mengubah, atau menghapus batas harga. Checker hanya melihat sebagai acuan.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/70 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase">
                            <th class="px-4 py-3">Nama Item</th>
                            <th class="px-4 py-3">Satuan</th>
                            <th class="px-4 py-3">Harga Maksimal</th>
                            <th class="px-4 py-3">Scope</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($priceCaps as $cap)
                            <tr class="hover:bg-slate-50/70 {{ $cap->is_active ? '' : 'opacity-60' }}">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $cap->item_name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $cap->unit }}</td>
                                <td class="px-4 py-3 font-bold text-teal-700">
                                    Rp {{ number_format($cap->max_price, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-semibold">
                                        {{ $cap->scope }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($cap->is_active)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap space-x-1.5">
                                    <button type="button"
                                            data-cap="{{ json_encode($cap) }}"
                                            data-url="{{ route('price-caps.update', $cap) }}"
                                            onclick="openEditCapModal(JSON.parse(this.dataset.cap), this.dataset.url)"
                                            class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 text-[11px] font-semibold transition">
                                        Edit
                                    </button>
                                    <form action="{{ route('price-caps.destroy', $cap) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Hapus standar harga {{ addslashes($cap->item_name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2.5 py-1.5 rounded-lg border border-rose-200 hover:bg-rose-50 text-rose-600 text-[11px] font-semibold transition">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada data batas harga.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    {{-- Modal Tambah Batas Harga --}}
    <div id="createCapModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800">Tambah Batas Harga</h3>
                <button type="button" onclick="closeCreateCapModal()" class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
            </div>
            <form action="{{ route('price-caps.store') }}" method="POST" class="p-5 space-y-3.5 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nama Item</label>
                    <input type="text" name="item_name" required placeholder="Contoh: Nasi Kotak / Kertas A4"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:border-teal-600">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Satuan</label>
                        <input type="text" name="unit" required placeholder="Contoh: box, rim, pcs"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:border-teal-600">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Scope</label>
                        <select name="scope" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white focus:outline-none focus:border-teal-600">
                            <option value="ALL">ALL (Semua)</option>
                            <option value="ORG">ORG (Organisasi)</option>
                            <option value="YAYASAN">YAYASAN (Sekolah/Pondok)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Harga Maksimal (Rp)</label>
                    <input type="text" name="max_price" required placeholder="25.000"
                           oninput="formatRupiahInput(this)"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 font-semibold focus:outline-none focus:border-teal-600">
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeCreateCapModal()" class="px-3.5 py-2 rounded-lg border border-slate-200 text-slate-600 font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit Batas Harga --}}
    <div id="editCapModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800">Edit Batas Harga</h3>
                <button type="button" onclick="closeEditCapModal()" class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
            </div>
            <form id="editCapForm" method="POST" class="p-5 space-y-3.5 text-xs">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nama Item</label>
                    <input type="text" name="item_name" id="editItemName" required
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:border-teal-600">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Satuan</label>
                        <input type="text" name="unit" id="editUnit" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:border-teal-600">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Scope</label>
                        <select name="scope" id="editScope" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white focus:outline-none focus:border-teal-600">
                            <option value="ALL">ALL (Semua)</option>
                            <option value="ORG">ORG (Organisasi)</option>
                            <option value="YAYASAN">YAYASAN (Sekolah/Pondok)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Harga Maksimal (Rp)</label>
                    <input type="text" name="max_price" id="editMaxPrice" required
                           oninput="formatRupiahInput(this)"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 font-semibold focus:outline-none focus:border-teal-600">
                </div>
                <label class="inline-flex items-center gap-2 text-slate-600 cursor-pointer">
                    <input type="checkbox" name="is_active" id="editIsActive" value="1" class="rounded border-slate-300 text-teal-600">
                    <span>Status Aktif</span>
                </label>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeEditCapModal()" class="px-3.5 py-2 rounded-lg border border-slate-200 text-slate-600 font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function formatRupiahInput(el) {
            const raw = el.value.replace(/\D/g, '');
            el.value = raw ? Number(raw).toLocaleString('id-ID') : '';
        }
        function openCreateCapModal() {
            document.getElementById('createCapModal').classList.remove('hidden');
        }
        function closeCreateCapModal() {
            document.getElementById('createCapModal').classList.add('hidden');
        }
        function openEditCapModal(cap, updateUrl) {
            document.getElementById('editCapForm').action = updateUrl;
            document.getElementById('editItemName').value = cap.item_name || '';
            document.getElementById('editUnit').value = cap.unit || '';
            document.getElementById('editScope').value = cap.scope || 'ALL';
            document.getElementById('editMaxPrice').value = Number(cap.max_price || 0).toLocaleString('id-ID');
            document.getElementById('editIsActive').checked = Boolean(cap.is_active);
            document.getElementById('editCapModal').classList.remove('hidden');
        }
        function closeEditCapModal() {
            document.getElementById('editCapModal').classList.add('hidden');
        }
    </script>
</body>
</html>