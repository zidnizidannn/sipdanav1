<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function poll(): JsonResponse
    {
        $user = Auth::user();
        $items = collect();

        // Sesuaikan query di bawah dengan kondisi status di SIPDANA Anda
        if ($user->role_code === 'CHECKER_ORG') {
            $items = Proposal::where('recipient_type', 'PEMBERI_ANGGARAN')
                ->where('status', Proposal::STATUS_WAITING_EXAMINATION)->latest('updated_at')->take(10)->get();
        } elseif ($user->role_code === 'CHECKER_YAYASAN') {
            $items = Proposal::where('recipient_type', 'YAYASAN')
                ->where('status', Proposal::STATUS_WAITING_EXAMINATION)->latest('updated_at')->take(10)->get();
        } elseif ($user->role_code === 'PEMBERI_ANGGARAN') {
            $items = Proposal::where('recipient_type', 'PEMBERI_ANGGARAN')
                ->where('status', Proposal::STATUS_WAITING_APPROVAL)->latest('updated_at')->take(10)->get();
        } elseif ($user->role_code === 'YAYASAN') {
            $items = Proposal::where('recipient_type', 'YAYASAN')
                ->where('status', Proposal::STATUS_WAITING_APPROVAL)->latest('updated_at')->take(10)->get();
        } elseif ($user->role_code === 'KU') {
            $items = Proposal::where('status', Proposal::STATUS_WAITING_DISBURSEMENT)
                ->latest('updated_at')->take(10)->get();
        } else {
            // Pengaju: notifikasi saat perlu revisi atau sudah cair
            $items = Proposal::where('user_id', $user->id)
                ->whereIn('status', [Proposal::STATUS_REVISION, Proposal::STATUS_DISBURSED])->latest('updated_at')->take(10)->get();
        }

        $data = $items->map(function (Proposal $p) {
            $statusText = match ($p->status) {
                Proposal::STATUS_WAITING_EXAMINATION  => 'Menunggu pemeriksaan Checker',
                Proposal::STATUS_WAITING_APPROVAL     => 'Menunggu persetujuan',
                Proposal::STATUS_WAITING_DISBURSEMENT => 'Menunggu pencairan (KU)',
                Proposal::STATUS_REVISION             => 'Perlu revisi',
                Proposal::STATUS_DISBURSED            => 'Dana sudah dicairkan',
                default                               => $p->status,
            };

            return [
                'key'     => "prop-{$p->id}-{$p->status}-{$p->updated_at->timestamp}",
                'title'   => "SIPDANA • {$p->code}",
                'message' => "{$p->title} — {$statusText}",
                'url'     => route('proposals.show', $p->id),
                'time'    => $p->updated_at->diffForHumans(),
            ];
        })->values();

        return response()->json(['items' => $data]);
    }
}