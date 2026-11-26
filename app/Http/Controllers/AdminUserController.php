<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public static function roleOptions(): array
    {
        return [
            'ADMIN'            => ['label' => 'Administrator Sistem',        'group' => 'ADMIN'],
            'PEMBERI_ANGGARAN' => ['label' => 'Pemberi Anggaran (Pusat)',    'group' => 'APPROVER'],
            'YAYASAN'          => ['label' => 'Yayasan Pendidikan',          'group' => 'YAYASAN'],
            'CHECKER_ORG'      => ['label' => 'Checker Organisasi',          'group' => 'CHECKER_ORG'],
            'CHECKER_YAYASAN'  => ['label' => 'Checker Yayasan',             'group' => 'CHECKER_YAYASAN'],
            'KU'               => ['label' => 'Keuangan (KU / Pencairan)',   'group' => 'KU'],
            'ORG_APPLICANT'    => ['label' => 'Pengaju Organisasi / Divisi', 'group' => 'ORG'],
            'SCHOOL_APPLICANT' => ['label' => 'Pengaju Sekolah / Pondok',    'group' => 'SCHOOL'],
        ];
    }

    private function ensureAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->isAdmin(), 403, 'Akses khusus Administrator.');
    }

    private function normalizePhone(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }
        return $digits;
    }

    public function index(Request $request)
    {
        $this->ensureAdmin();

        $search = trim((string) $request->query('q', ''));
        $groupFilter = trim((string) $request->query('group', ''));

        $users = User::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('wa_phone', 'like', "%{$search}%")
                        ->orWhere('role_code', 'like', "%{$search}%");
                });
            })
            ->when($groupFilter !== '', fn ($q) => $q->where('entity_group', $groupFilter))
            ->orderByRaw("CASE WHEN role_code = 'ADMIN' THEN 0 ELSE 1 END")
            ->orderBy('entity_group')
            ->orderBy('name')
            ->get();

        $staffRoles = [
            'PEMBERI_ANGGARAN',
            'YAYASAN',
            'CHECKER_ORG',
            'CHECKER_YAYASAN',
            'KU',
        ];

        return view('admin.users.index', [
            'users'          => $users,
            'roleOptions'    => self::roleOptions(),
            'search'         => $search,
            'groupFilter'    => $groupFilter,
            'totalUsers'     => User::count(),
            'staffCount'     => User::whereIn('role_code', $staffRoles)->count(),
            'applicantCount' => User::whereNotIn('role_code', [...$staffRoles, 'ADMIN'])->count(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $request->merge([
            'username' => strtolower(trim((string) $request->input('username'))),
            'wa_phone' => $this->normalizePhone($request->input('wa_phone')),
        ]);

        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:120'],
            'username'         => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'wa_phone'         => ['nullable', 'string', 'max:25'],
            'password'         => ['required', 'string', 'min:6'],
            'role_preset'      => ['required', Rule::in(array_keys(self::roleOptions()))],
            'custom_role_code' => ['nullable', 'string', 'max:40'],
        ], [
            'username.unique'     => 'Username tersebut sudah digunakan akun lain.',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip (-), atau underscore (_).',
        ]);

        $preset = self::roleOptions()[$validated['role_preset']];

        $roleCode = $validated['role_preset'];
        if (in_array($validated['role_preset'], ['ORG_APPLICANT', 'SCHOOL_APPLICANT'], true)) {
            $custom = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', trim((string) ($validated['custom_role_code'] ?? ''))));
            $roleCode = $custom !== '' ? $custom : $validated['role_preset'];
        }

        User::create([
            'name'         => trim($validated['name']),
            'username'     => $validated['username'],
            'email'        => null,
            'wa_phone'     => $validated['wa_phone'],
            'password'     => Hash::make($validated['password']),
            'role_code'    => $roleCode,
            'entity_group' => $preset['group'],
            'yayasan_mode' => 'FUND_PROVIDER',
        ]);

        return back()->with('success', "Akun '{$validated['name']}' (@{$validated['username']}) berhasil dibuat.");
    }

    public function update(Request $request, User $user)
    {
        $this->ensureAdmin();

        $request->merge([
            'username' => strtolower(trim((string) $request->input('username'))),
            'wa_phone' => $this->normalizePhone($request->input('wa_phone')),
        ]);

        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:120'],
            'username'         => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'wa_phone'         => ['nullable', 'string', 'max:25'],
            'password'         => ['nullable', 'string', 'min:6'],
            'role_preset'      => ['nullable', Rule::in(array_keys(self::roleOptions()))],
            'custom_role_code' => ['nullable', 'string', 'max:40'],
        ], [
            'username.unique'     => 'Username tersebut sudah digunakan akun lain.',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip (-), atau underscore (_).',
        ]);

        $payload = [
            'name'     => trim($validated['name']),
            'username' => $validated['username'],
            'wa_phone' => $validated['wa_phone'],
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        if ($user->id !== Auth::id() && !empty($validated['role_preset'])) {
            $preset = self::roleOptions()[$validated['role_preset']];
            $roleCode = $validated['role_preset'];

            if (in_array($validated['role_preset'], ['ORG_APPLICANT', 'SCHOOL_APPLICANT'], true)) {
                $custom = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', trim((string) ($validated['custom_role_code'] ?? ''))));
                $roleCode = $custom !== '' ? $custom : $validated['role_preset'];
            }

            $payload['role_code'] = $roleCode;
            $payload['entity_group'] = $preset['group'];
        }

        $user->update($payload);

        return back()->with('success', "Data akun '{$user->name}' berhasil diperbarui.");
    }

    public function destroy(User $user)
    {
        $this->ensureAdmin();

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Admin yang sedang digunakan.');
        }

        if (Proposal::where('user_id', $user->id)->exists()) {
            return back()->with('error', "Akun '{$user->name}' tidak dapat dihapus karena sudah memiliki riwayat pengajuan.");
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "Akun '{$name}' berhasil dihapus.");
    }
}