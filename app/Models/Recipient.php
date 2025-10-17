<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recipient extends Model
{
    protected $fillable = [
        'campaign_id',
        'email',
        'name',
        'status',
        'last_error',
        'attempt_count',
        'sent_at',
    ];

    protected $casts = [
        'attempt_count' => 'integer',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the campaign that owns the recipient.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Scope to get recipients by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending recipients.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get sent recipients.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to get failed recipients.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
