<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Akun — Admin SIPDANA</title>
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
                    <p class="text-[11px] text-slate-500">Manajemen Akun Pengguna</p>
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
                    <div class="text-[11px] text-slate-400">{{ '@' . auth()->user()->username }}</div>
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

        {{-- Notifikasi Flash & Error --}}
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

        {{-- Ringkasan Akun --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase">Total Akun</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ $totalUsers }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase">Akun Pengaju (Org / Sekolah)</p>
                <p class="text-2xl font-bold text-teal-700 mt-1">{{ $applicantCount }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase">Checker, Approver &amp; KU</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ $staffCount }}</p>
            </div>
        </div>

        {{-- Bar Pencarian, Filter & Tambah Akun --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-center gap-2 flex-1">
                <input type="text" name="q" value="{{ $search }}"
                       placeholder="Cari nama, username, nomor WA, atau role..."
                       class="w-full sm:w-64 px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-none focus:border-teal-600">
                <select name="group" onchange="this.form.submit()"
                        class="px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs focus:outline-none focus:border-teal-600">
                    <option value="">Semua Grup</option>
                    <option value="ADMIN" @selected($groupFilter === 'ADMIN')>Admin</option>
                    <option value="ORG" @selected($groupFilter === 'ORG')>Pengaju Organisasi</option>
                    <option value="SCHOOL" @selected($groupFilter === 'SCHOOL')>Pengaju Sekolah</option>
                    <option value="CHECKER_ORG" @selected($groupFilter === 'CHECKER_ORG')>Checker Org</option>
                    <option value="CHECKER_YAYASAN" @selected($groupFilter === 'CHECKER_YAYASAN')>Checker Yayasan</option>
                    <option value="YAYASAN" @selected($groupFilter === 'YAYASAN')>Yayasan</option>
                    <option value="APPROVER" @selected($groupFilter === 'APPROVER')>Pemberi Anggaran</option>
                    <option value="KU" @selected($groupFilter === 'KU')>Keuangan (KU)</option>
                </select>
                <button type="submit" class="px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold transition">
                    Cari
                </button>
                @if($search !== '' || $groupFilter !== '')
                    <a href="{{ route('admin.users.index') }}" class="text-xs text-slate-500 hover:text-slate-800 px-2">Reset</a>
                @endif
            </form>

            <button type="button" onclick="openCreateUserModal()"
                    class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition shrink-0">
                + Tambah Akun
            </button>
        </div>

        {{-- Tabel Daftar User --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Daftar Akun Sistem</h2>
                    <p class="text-[11px] text-slate-500">Login menggunakan username. Nomor WA disimpan untuk kebutuhan notifikasi WhatsApp.</p>
                </div>
                <span class="text-[11px] text-slate-500">{{ $users->count() }} akun ditampilkan</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/70 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase">
                            <th class="px-4 py-3">Nama &amp; Username</th>
                            <th class="px-4 py-3">Nomor WA</th>
                            <th class="px-4 py-3">Kode Role</th>
                            <th class="px-4 py-3">Grup</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($users as $u)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">
                                        {{ $u->name }}
                                        @if($u->id === auth()->id())
                                            <span class="ml-1 px-1.5 py-0.5 rounded bg-teal-50 text-teal-700 border border-teal-200 text-[10px]">Anda</span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ '@' . $u->username }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if($u->wa_phone)
                                        <span class="inline-flex items-center gap-1 text-emerald-700 font-medium">
                                            <i class="fa-brands fa-whatsapp"></i> {{ $u->wa_phone }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic">Belum diisi</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-700 font-semibold text-[11px]">
                                        {{ $u->role_code }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $u->entity_group }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap space-x-1.5">
                                    <button type="button"
                                            data-user="{{ json_encode($u) }}"
                                            data-url="{{ route('admin.users.update', $u) }}"
                                            data-self="{{ $u->id === auth()->id() ? '1' : '0' }}"
                                            onclick="openEditUserModal(JSON.parse(this.dataset.user), this.dataset.url, this.dataset.self === '1')"
                                            class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 text-[11px] font-semibold transition">
                                        Edit
                                    </button>
                                    @if($u->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $u) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Hapus akun {{ addslashes($u->name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-2.5 py-1.5 rounded-lg border border-rose-200 hover:bg-rose-50 text-rose-600 text-[11px] font-semibold transition">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-slate-400">Tidak ada akun yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    {{-- Modal Tambah Akun --}}
    <div id="createUserModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800">Tambah Akun Baru</h3>
                <button type="button" onclick="closeCreateUserModal()" class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
            </div>
            <form action="{{ route('admin.users.store') }}" method="POST" class="p-5 space-y-3.5 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nama Akun / Unit</label>
                    <input type="text" name="name" required placeholder="Misal: Panitia Kurban / SMA Unit 2"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:border-teal-600">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Username Login</label>
                        <input type="text" name="username" required placeholder="misal: panitia_kurban"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:border-teal-600">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nomor WA (Opsional)</label>
                        <input type="text" name="wa_phone" placeholder="081234567890"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:border-teal-600">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Peran (Role)</label>
                        <select name="role_preset" id="createRolePreset" onchange="toggleCreateCustomCode()" required
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white focus:outline-none focus:border-teal-600">
                            @foreach($roleOptions as $code => $opt)
                                <option value="{{ $code }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Password Awal</label>
                        <input type="text" name="password" value="password123" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:border-teal-600">
                    </div>
                </div>
                <div id="createCustomCodeWrap" class="hidden">
                    <label class="block font-semibold text-slate-700 mb-1">Kode Singkat Pengaju (Opsional)</label>
                    <input type="text" name="custom_role_code" placeholder="Contoh: BEM, PANITIA_A, SMA_2"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:border-teal-600">
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeCreateUserModal()" class="px-3.5 py-2 rounded-lg border border-slate-200 text-slate-600 font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold">Buat Akun</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit Akun --}}
    <div id="editUserModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800">Edit Akun</h3>
                <button type="button" onclick="closeEditUserModal()" class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
            </div>
            <form id="editUserForm" method="POST" class="p-5 space-y-3.5 text-xs">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nama Akun</label>
                    <input type="text" name="name" id="editName" required
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:border-teal-600">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Username Login</label>
                        <input type="text" name="username" id="editUsername" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:border-teal-600">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nomor WA</label>
                        <input type="text" name="wa_phone" id="editWaPhone" placeholder="081234567890"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:border-teal-600">
                    </div>
                </div>
                <div id="editRoleSection" class="space-y-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Peran (Role)</label>
                        <select name="role_preset" id="editRolePreset" onchange="toggleEditCustomCode()"
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white focus:outline-none focus:border-teal-600">
                            @foreach($roleOptions as $code => $opt)
                                <option value="{{ $code }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="editCustomCodeWrap" class="hidden">
                        <label class="block font-semibold text-slate-700 mb-1">Kode Singkat Pengaju (Opsional)</label>
                        <input type="text" name="custom_role_code" id="editCustomCode"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:border-teal-600">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Reset Password (Opsional)</label>
                    <input type="text" name="password" placeholder="Kosongkan jika tidak ingin mengubah password"
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:border-teal-600">
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeEditUserModal()" class="px-3.5 py-2 rounded-lg border border-slate-200 text-slate-600 font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateUserModal() {
            document.getElementById('createUserModal').classList.remove('hidden');
            toggleCreateCustomCode();
        }
        function closeCreateUserModal() {
            document.getElementById('createUserModal').classList.add('hidden');
        }
        function toggleCreateCustomCode() {
            const val = document.getElementById('createRolePreset').value;
            document.getElementById('createCustomCodeWrap').classList.toggle('hidden', !['ORG_APPLICANT', 'SCHOOL_APPLICANT'].includes(val));
        }

        function mapUserToPreset(user) {
            if (user.entity_group === 'ORG') return 'ORG_APPLICANT';
            if (user.entity_group === 'SCHOOL') return 'SCHOOL_APPLICANT';
            return user.role_code;
        }
        function toggleEditCustomCode() {
            const val = document.getElementById('editRolePreset').value;
            document.getElementById('editCustomCodeWrap').classList.toggle('hidden', !['ORG_APPLICANT', 'SCHOOL_APPLICANT'].includes(val));
        }
        function openEditUserModal(user, updateUrl, isSelf) {
            document.getElementById('editUserForm').action = updateUrl;
            document.getElementById('editName').value = user.name || '';
            document.getElementById('editUsername').value = user.username || '';
            document.getElementById('editWaPhone').value = user.wa_phone || '';

            const roleSection = document.getElementById('editRoleSection');
            roleSection.classList.toggle('hidden', isSelf);

            if (!isSelf) {
                const preset = mapUserToPreset(user);
                document.getElementById('editRolePreset').value = preset;
                document.getElementById('editCustomCode').value = ['ORG_APPLICANT', 'SCHOOL_APPLICANT'].includes(preset) ? (user.role_code || '') : '';
                toggleEditCustomCode();
            }

            document.getElementById('editUserModal').classList.remove('hidden');
        }
        function closeEditUserModal() {
            document.getElementById('editUserModal').classList.add('hidden');
        }
    </script>
</body>
</html>