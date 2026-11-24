<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalHistory extends Model
{
    protected $fillable = [
        'proposal_id',
        'actor_id',
        'actor_label',
        'action',
        'note',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}