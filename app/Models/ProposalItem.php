<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalItem extends Model
{
    protected $fillable = [
        'proposal_id',
        'price_cap_id',
        'name',
        'spec',
        'qty',
        'unit',
        'unit_price',
        'is_checked',
        'revision_note',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'integer',
        'is_checked' => 'boolean',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function priceCap(): BelongsTo
    {
        return $this->belongsTo(PriceCap::class);
    }

    public function getSubtotalAttribute(): int
    {
        return $this->qty * $this->unit_price;
    }
}