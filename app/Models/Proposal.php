<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proposal extends Model
{
    public const STATUS_WAITING_EXAMINATION = 'WAITING_EXAMINATION'; // Menunggu Pemeriksaan
    public const STATUS_REVISION            = 'REVISION';            // Revisi
    public const STATUS_WAITING_APPROVAL    = 'WAITING_APPROVAL';    // Menunggu Persetujuan
    public const STATUS_WAITING_DISBURSEMENT= 'WAITING_DISBURSEMENT';// Menunggu Pencairan
    public const STATUS_COMPLETED           = 'COMPLETED';           // Selesai

    protected $fillable = [
        'code',
        'title',
        'user_id',
        'applicant_role_code',
        'applicant_group',
        'recipient_type',
        'disbursement_account',
        'submission_date',
        'description',
        'attachment_path',
        'status',
        'escalated_from_id',
        'escalated_from_school',
        'escalated_to_id',
        'disbursement_note',
        'disbursed_at',
    ];

    protected $casts = [
        'submission_date' => 'date',
        'disbursed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProposalItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ProposalHistory::class)->latest();
    }

    public function getTotalAmountAttribute(): int
    {
        return $this->items->sum(fn ($item) => $item->qty * $item->unit_price);
    }
}