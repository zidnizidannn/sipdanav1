<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - SIPDANA</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-4xl grid grid-cols-1 {{ $demoUsers->isNotEmpty() ? 'md:grid-cols-2' : 'max-w-md' }} gap-6 items-start">
        {{-- Form Login --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
            <div class="flex items-center gap-2.5 mb-6">
                <div class="w-9 h-9 rounded-lg bg-slate-900 text-white flex items-center justify-center font-bold text-sm">SD</div>
                <div>
                    <h1 class="text-lg font-bold text-slate-900 leading-tight">SIPDANA</h1>
                    <p class="text-xs text-slate-500">Sistem Pengajuan &amp; Persetujuan Dana</p>
                </div>
            </div>

            @if($errors->any())
                <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-xs text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form id="loginForm" action="{{ route('login.submit') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="username" class="block text-xs font-semibold text-slate-700 mb-1">Username</label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}"
                        required autofocus autocomplete="username" placeholder="Masukkan username"
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:border-teal-600">
                    @error('username')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" id="password" name="password" required
                           placeholder="••••••••"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                </div>

                <label class="flex items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300">
                    <span>Ingat sesi login saya</span>
                </label>

                <button type="submit"
                        class="w-full rounded-lg bg-slate-900 text-white py-2.5 text-sm font-medium hover:bg-slate-800 transition">
                    Masuk
                </button>
            </form>
        </div>

        {{-- Panel Akun Demo (Hanya Tampil di Local Dev) --}}
        @if($demoUsers->isNotEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="mb-3">
                    <h2 class="text-sm font-semibold text-slate-900">Akses Cepat Uji Coba (Lokal)</h2>
                    <p class="text-xs text-slate-500">Klik peran di bawah untuk langsung masuk (password default: <code class="bg-slate-100 px-1 rounded">password123</code>).</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-[420px] overflow-y-auto pr-1">
                    @foreach($demoUsers as $demo)
                        <button type="button"
                                onclick="quickLogin('{{ $demo->email }}')"
                                class="text-left p-2.5 rounded-lg border border-slate-200 hover:border-slate-900 hover:bg-slate-50 transition">
                            <div class="text-xs font-semibold text-slate-900 truncate">{{ $demo->name }}</div>
                            <div class="text-[11px] text-slate-500 truncate">{{ $demo->role_code }}</div>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <script>
        function quickLogin(email) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = 'password123';
            document.getElementById('loginForm').submit();
        }
    </script>
</body>
</html>