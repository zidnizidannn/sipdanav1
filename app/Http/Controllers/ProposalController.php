<?php

namespace App\Http\Controllers;

use App\Models\PriceCap;
use App\Models\Proposal;
use App\Models\ProposalHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProposalController extends Controller
{
    /**
     * Dashboard & daftar pengajuan sesuai peran pengguna.
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $query = Proposal::with(['user', 'items'])->latest();

        // Filter scope berdasarkan peran aktif
        if ($user->role_code === 'CHECKER_ORG') {
            $query->where('recipient_type', 'PEMBERI_ANGGARAN');
        } elseif ($user->role_code === 'CHECKER_YAYASAN') {
            $query->where('recipient_type', 'YAYASAN');
        } elseif ($user->role_code === 'PEMBERI_ANGGARAN') {
            $query->where('recipient_type', 'PEMBERI_ANGGARAN');
        } elseif ($user->role_code === 'YAYASAN' && $user->yayasan_mode === 'FUND_PROVIDER') {
            $query->where('recipient_type', 'YAYASAN');
        } elseif ($user->isKu()) {} 
        else {
            // Pengaju (Organisasi, Tim Pembangunan, PPG, Sekolah, atau Yayasan mode Pengaju)
            $query->where('user_id', $user->id);
        }

        // 1. Ambil seluruh data sesuai peran DULU untuk angka 6 kartu statistik
        $allProposals = $query->get();

        $stats = [
            'waiting_examination'  => $allProposals->where('status', Proposal::STATUS_WAITING_EXAMINATION)->count(),
            'revision'             => $allProposals->where('status', Proposal::STATUS_REVISION)->count(),
            'waiting_approval'     => $allProposals->where('status', Proposal::STATUS_WAITING_APPROVAL)->count(),
            'waiting_disbursement' => $allProposals->where('status', Proposal::STATUS_WAITING_DISBURSEMENT)->count(),
            'completed'            => $allProposals->where('status', Proposal::STATUS_COMPLETED)->count(),
            'total_count'          => $allProposals->count(),
            'total_nominal'        => $allProposals->sum(fn ($p) => $p->items->sum(fn ($i) => $i->qty * $i->unit_price)),
        ];

        // 2. Baru filter daftar tabel jika user mengklik kartu/dropdown status
        $proposals = $request->filled('status')
            ? $allProposals->where('status', (string) $request->input('status'))->values()
            : $allProposals;

        return view('proposals.index', compact('proposals', 'stats', 'user'));
    }

    /**
     * Form buat pengajuan dana baru (khusus peran pengaju).
     */
    public function create()
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isApplicant(), 403, 'Peran Anda tidak memiliki akses untuk membuat pengajuan.');

        $priceCaps = $this->getAvailablePriceCaps($user);

        return view('proposals.create', compact('priceCaps', 'user'));
    }

    /**
     * Simpan pengajuan baru + validasi standar harga maksimal di backend.
     */
    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isApplicant(), 403, 'Peran Anda tidak memiliki akses untuk membuat pengajuan.');

        $validated = $this->validateProposalPayload($request);
        $preparedItems = $this->validateAndPrepareItems($validated['items']);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        $recipientType = $user->entity_group === 'SCHOOL' ? 'YAYASAN' : 'PEMBERI_ANGGARAN';

        $proposal = DB::transaction(function () use ($user, $validated, $preparedItems, $attachmentPath, $recipientType) {
            $proposal = Proposal::create([
                'code'                 => $this->generateCode(),
                'title'                => $validated['title'],
                'user_id'              => $user->id,
                'applicant_role_code'  => $user->role_code,
                'applicant_group'      => $user->entity_group,
                'recipient_type'       => $recipientType,
                'disbursement_account' => $validated['disbursement_account'],
                'submission_date'      => $validated['submission_date'],
                'description'          => $validated['description'] ?? null,
                'attachment_path'      => $attachmentPath,
                'status'               => Proposal::STATUS_WAITING_EXAMINATION,
            ]);

            $proposal->items()->createMany($preparedItems);

            ProposalHistory::create([
                'proposal_id' => $proposal->id,
                'actor_id'    => $user->id,
                'actor_label' => $user->name,
                'action'      => 'Mengajukan Dana',
                'note'        => 'Pengajuan dikirim ke ' . ($recipientType === 'YAYASAN' ? 'Checker Yayasan' : 'Checker Organisasi') . '.',
            ]);

            return $proposal;
        });

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', "Pengajuan {$proposal->code} berhasil dikirim dan menunggu pemeriksaan.");
    }

    /**
     * Detail pengajuan beserta rincian item dan riwayat status.
     */
    public function show(Proposal $proposal)
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($this->canViewProposal($user, $proposal), 403, 'Anda tidak berhak mengakses pengajuan ini.');

        $proposal->load(['user', 'items.priceCap', 'histories']);

        return view('proposals.show', compact('proposal', 'user'));
    }

    /**
     * Proses pemeriksaan checklist per item oleh Checker Organisasi / Checker Yayasan.
     */
    public function examine(Request $request, Proposal $proposal)
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($this->canExamine($user, $proposal), 403, 'Anda tidak berhak memeriksa pengajuan ini.');

        if ($proposal->status !== Proposal::STATUS_WAITING_EXAMINATION) {
            return back()->with('error', 'Pengajuan ini tidak berada pada tahap Menunggu Pemeriksaan.');
        }

        $proposal->load('items');
        $submittedItems = $request->input('items', []);
        $errors = [];
        $hasUnchecked = false;
        $revisionSummary = [];

        foreach ($proposal->items as $item) {
            $input = $submittedItems[$item->id] ?? [];
            $isChecked = filter_var($input['is_checked'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $note = trim((string) ($input['revision_note'] ?? ''));

            if (!$isChecked) {
                $hasUnchecked = true;
                if ($note === '') {
                    $errors["items.{$item->id}.revision_note"] = "Catatan revisi wajib diisi untuk item '{$item->name}' yang tidak dicentang.";
                } else {
                    $revisionSummary[] = "{$item->name}: {$note}";
                }
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($proposal, $submittedItems, $hasUnchecked, $revisionSummary, $user) {
            foreach ($proposal->items as $item) {
                $input = $submittedItems[$item->id] ?? [];
                $isChecked = filter_var($input['is_checked'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $note = $isChecked ? null : trim((string) ($input['revision_note'] ?? ''));

                $item->update([
                    'is_checked'    => $isChecked,
                    'revision_note' => $note,
                ]);
            }

            if ($hasUnchecked) {
                $proposal->update(['status' => Proposal::STATUS_REVISION]);
                ProposalHistory::create([
                    'proposal_id' => $proposal->id,
                    'actor_id'    => $user->id,
                    'actor_label' => $user->name,
                    'action'      => 'Dikembalikan untuk Revisi',
                    'note'        => implode(' | ', $revisionSummary),
                ]);
            } else {
                $proposal->update(['status' => Proposal::STATUS_WAITING_APPROVAL]);
                ProposalHistory::create([
                    'proposal_id' => $proposal->id,
                    'actor_id'    => $user->id,
                    'actor_label' => $user->name,
                    'action'      => 'Lolos Pemeriksaan',
                    'note'        => 'Seluruh item telah diverifikasi dan diteruskan ke tahap Persetujuan.',
                ]);
            }
        });

        $message = $hasUnchecked
            ? 'Pengajuan dikembalikan ke pengaju untuk direvisi.'
            : 'Seluruh item lolos pemeriksaan. Pengajuan diteruskan ke tahap Persetujuan.';

        return redirect()->route('proposals.show', $proposal)->with('success', $message);
    }

    /**
     * Form edit untuk pengajuan yang berstatus Revisi (khusus pemilik pengajuan).
     */
    public function edit(Proposal $proposal)
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless(
            $proposal->user_id === $user->id && $proposal->status === Proposal::STATUS_REVISION,
            403,
            'Hanya pemilik pengajuan berstatus Revisi yang dapat mengedit.'
        );

        $proposal->load('items');
        $priceCaps = $this->getAvailablePriceCaps($user);

        return view('proposals.edit', compact('proposal', 'priceCaps', 'user'));
    }

    /**
     * Simpan perbaikan revisi dan ajukan ulang ke Checker.
     */
    public function resubmit(Request $request, Proposal $proposal)
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless(
            $proposal->user_id === $user->id && $proposal->status === Proposal::STATUS_REVISION,
            403,
            'Hanya pemilik pengajuan berstatus Revisi yang dapat mengajukan ulang.'
        );

        $validated = $this->validateProposalPayload($request);
        $preparedItems = $this->validateAndPrepareItems($validated['items']);

        $attachmentPath = $proposal->attachment_path;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        DB::transaction(function () use ($proposal, $validated, $preparedItems, $attachmentPath, $user) {
            $proposal->update([
                'title'                => $validated['title'],
                'disbursement_account' => $validated['disbursement_account'],
                'submission_date'      => $validated['submission_date'],
                'description'          => $validated['description'] ?? null,
                'attachment_path'      => $attachmentPath,
                'status'               => Proposal::STATUS_WAITING_EXAMINATION,
            ]);

            // Ganti rincian item dengan hasil revisi terbaru
            $proposal->items()->delete();
            $proposal->items()->createMany($preparedItems);

            ProposalHistory::create([
                'proposal_id' => $proposal->id,
                'actor_id'    => $user->id,
                'actor_label' => $user->name,
                'action'      => 'Mengajukan Ulang (Revisi)',
                'note'        => 'Item telah diperbaiki dan dikirim kembali untuk pemeriksaan.',
            ]);
        });

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Perbaikan berhasil disimpan dan diajukan kembali ke Checker.');
    }

    /**
     * Persetujuan dana oleh Pemberi Anggaran atau Yayasan (mode Pemberi Dana).
     */
    public function approve(Request $request, Proposal $proposal)
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($this->canApprove($user, $proposal), 403, 'Anda tidak berhak menyetujui pengajuan ini.');

        if ($proposal->status !== Proposal::STATUS_WAITING_APPROVAL) {
            return back()->with('error', 'Pengajuan ini tidak berada pada tahap Menunggu Persetujuan.');
        }

        $note = trim((string) $request->input('approval_note', ''));

        DB::transaction(function () use ($proposal, $user, $note) {
            $proposal->update([
                'status' => Proposal::STATUS_WAITING_DISBURSEMENT,
            ]);

            ProposalHistory::create([
                'proposal_id' => $proposal->id,
                'actor_id'    => $user->id,
                'actor_label' => $user->name,
                'action'      => 'Disetujui (ACC)',
                'note'        => $note !== '' ? $note : 'Pengajuan disetujui dan diteruskan ke antrean pencairan KU.',
            ]);
        });

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Pengajuan disetujui dan masuk antrean Menunggu Pencairan.');
    }

    /**
     * Eskalasi pengajuan Sekolah dari Yayasan ke Pemberi Anggaran Pusat.
     */
    public function escalate(Request $request, Proposal $proposal)
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless(
            $user->role_code === 'YAYASAN'
                && $user->yayasan_mode === 'FUND_PROVIDER'
                && $proposal->recipient_type === 'YAYASAN',
            403,
            'Hanya Yayasan (mode Pemberi Dana) yang dapat mengeskalasi pengajuan sekolah.'
        );

        if ($proposal->status !== Proposal::STATUS_WAITING_APPROVAL || $proposal->escalated_to_id !== null) {
            return back()->with('error', 'Pengajuan ini tidak dapat dieskalasi.');
        }

        $proposal->load('items');
        $note = trim((string) $request->input('escalation_note', ''));

        $escalatedProposal = DB::transaction(function () use ($proposal, $user, $note) {
            $newProposal = Proposal::create([
                'code'                  => $this->generateCode(),
                'title'                 => "[Eskalasi {$proposal->applicant_role_code}] {$proposal->title}",
                'user_id'               => $user->id,
                'applicant_role_code'   => 'YAYASAN',
                'applicant_group'       => 'MAIN_ENTITY',
                'recipient_type'        => 'PEMBERI_ANGGARAN',
                'disbursement_account'  => $proposal->disbursement_account,
                'submission_date'       => now()->toDateString(),
                'description'           => $proposal->description,
                'attachment_path'       => $proposal->attachment_path,
                'status'                => Proposal::STATUS_WAITING_EXAMINATION,
                'escalated_from_id'     => $proposal->id,
                'escalated_from_school' => $proposal->applicant_role_code,
            ]);

            foreach ($proposal->items as $item) {
                $newProposal->items()->create([
                    'price_cap_id'  => $item->price_cap_id,
                    'name'          => $item->name,
                    'spec'          => $item->spec,
                    'qty'           => $item->qty,
                    'unit'          => $item->unit,
                    'unit_price'    => $item->unit_price,
                    'is_checked'    => true,
                    'revision_note' => null,
                ]);
            }

            $proposal->update([
                'escalated_to_id' => $newProposal->id,
            ]);

            ProposalHistory::create([
                'proposal_id' => $proposal->id,
                'actor_id'    => $user->id,
                'actor_label' => $user->name,
                'action'      => 'Dieskalasi ke Pusat',
                'note'        => "Diteruskan ke Pemberi Anggaran dengan kode {$newProposal->code}." . ($note !== '' ? " Catatan: {$note}" : ''),
            ]);

            ProposalHistory::create([
                'proposal_id' => $newProposal->id,
                'actor_id'    => $user->id,
                'actor_label' => $user->name,
                'action'      => 'Pengajuan Eskalasi Dibuat',
                'note'        => "Berasal dari pengajuan {$proposal->code} ({$proposal->applicant_role_code}).",
            ]);

            return $newProposal;
        });

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', "Pengajuan berhasil dieskalasi ke Pusat dengan kode {$escalatedProposal->code}.");
    }

    /**
     * Pencairan dana & penutupan status menjadi Selesai oleh KU.
     */
    public function disburse(Request $request, Proposal $proposal)
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isKu(), 403, 'Hanya KU (Keuangan) yang dapat mencairkan dana.');

        if ($proposal->status !== Proposal::STATUS_WAITING_DISBURSEMENT) {
            return back()->with('error', 'Pengajuan ini belum siap dicairkan.');
        }

        $validated = $request->validate([
            'disbursement_note' => ['required', 'string', 'max:500'],
        ], [
            'disbursement_note.required' => 'Catatan pencairan / nomor referensi transfer wajib diisi.',
        ]);

        DB::transaction(function () use ($proposal, $validated, $user) {
            $proposal->update([
                'status'            => Proposal::STATUS_COMPLETED,
                'disbursement_note' => $validated['disbursement_note'],
                'disbursed_at'      => now(),
            ]);

            ProposalHistory::create([
                'proposal_id' => $proposal->id,
                'actor_id'    => $user->id,
                'actor_label' => $user->name,
                'action'      => 'Dana Dicairkan (Selesai)',
                'note'        => $validated['disbursement_note'],
            ]);
        });

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', "Dana untuk {$proposal->code} telah ditandai cair dan selesai.");
    }

    // =========================================================================
    // Helper Validasi & Hak Akses
    // =========================================================================

    private function validateProposalPayload(Request $request): array
    {
        return $request->validate([
            'title'                => ['required', 'string', 'max:200'],
            'disbursement_account' => ['required', 'string', 'max:255'],
            'submission_date'      => ['required', 'date'],
            'description'          => ['nullable', 'string'],
            'attachment'           => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.price_cap_id' => ['nullable', 'integer', 'exists:price_caps,id'],
            'items.*.name'         => ['required', 'string', 'max:200'],
            'items.*.spec'         => ['nullable', 'string', 'max:255'],
            'items.*.qty'          => ['required', 'integer', 'min:1'],
            'items.*.unit'         => ['required', 'string', 'max:30'],
            'items.*.unit_price'   => ['required', 'integer', 'min:1'],
        ], [
            'items.required' => 'Minimal harus ada 1 rincian item pengajuan.',
        ]);
    }

    private function validateAndPrepareItems(array $items): array
    {
        $prepared = [];

        foreach ($items as $item) {
            $priceCapId = !empty($item['price_cap_id']) ? (int) $item['price_cap_id'] : null;
            $name = $item['name'];
            $unit = $item['unit'];
            $unitPrice = (int) $item['unit_price'];

            if ($priceCapId) {
                $cap = PriceCap::where('id', $priceCapId)->where('is_active', true)->first();
                if ($cap) {
                    // Kunci nama, satuan, dan harga fix sesuai master standar harga
                    $name = $cap->item_name;
                    $unit = $cap->unit;
                    $unitPrice = (int) $cap->max_price;
                }
            }

            $prepared[] = [
                'price_cap_id'  => $priceCapId,
                'name'          => $name,
                'spec'          => $item['spec'] ?? null,
                'qty'           => (int) $item['qty'],
                'unit'          => $unit,
                'unit_price'    => $unitPrice,
                'is_checked'    => true,
                'revision_note' => null,
            ];
        }

        return $prepared;
    }

    private function getAvailablePriceCaps(User $user)
    {
        $scope = $user->entity_group === 'SCHOOL' ? 'YAYASAN' : 'ORG';

        return PriceCap::where('is_active', true)
            ->whereIn('scope', ['ALL', $scope])
            ->orderBy('item_name')
            ->get();
    }

    private function generateCode(): string
    {
        $nextNumber = (Proposal::max('id') ?? 0) + 1;
        return 'PD-' . now()->format('Y') . '-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    private function canViewProposal(User $user, Proposal $proposal): bool
    {
        if ($proposal->user_id === $user->id) {
            return true;
        }
        if ($user->role_code === 'CHECKER_ORG' && $proposal->recipient_type === 'PEMBERI_ANGGARAN') {
            return true;
        }
        if ($user->role_code === 'CHECKER_YAYASAN' && $proposal->recipient_type === 'YAYASAN') {
            return true;
        }
        if ($user->role_code === 'PEMBERI_ANGGARAN' && $proposal->recipient_type === 'PEMBERI_ANGGARAN') {
            return true;
        }
        if ($user->role_code === 'YAYASAN' && $user->yayasan_mode === 'FUND_PROVIDER' && $proposal->recipient_type === 'YAYASAN') {
            return true;
        }
        // if ($user->isKu() && in_array($proposal->status, [Proposal::STATUS_WAITING_DISBURSEMENT, Proposal::STATUS_COMPLETED])) {
        //     return true;
        // }
        if ($user->isKu()) {
            return true;
        }

        return false;
    }

    private function canExamine(User $user, Proposal $proposal): bool
    {
        return ($user->role_code === 'CHECKER_ORG' && $proposal->recipient_type === 'PEMBERI_ANGGARAN')
            || ($user->role_code === 'CHECKER_YAYASAN' && $proposal->recipient_type === 'YAYASAN');
    }

    private function canApprove(User $user, Proposal $proposal): bool
    {
        return ($user->role_code === 'PEMBERI_ANGGARAN' && $proposal->recipient_type === 'PEMBERI_ANGGARAN')
            || ($user->role_code === 'YAYASAN' && $user->yayasan_mode === 'FUND_PROVIDER' && $proposal->recipient_type === 'YAYASAN');
    }
}