<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'sender_id',
        'smtp_configuration_id',
        'template_id',
        'name',
        'subject',
        'body',
        'total_recipients',
        'status',
    ];

    protected $casts = [
        'total_recipients' => 'integer',
    ];

    /**
     * Get the sender that owns the campaign.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(Sender::class);
    }

    /**
     * Get the SMTP configuration for the campaign.
     */
    public function smtpConfiguration(): BelongsTo
    {
        return $this->belongsTo(SmtpConfiguration::class);
    }

    /**
     * Get the recipients for the campaign.
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(Recipient::class);
    }

    /**
     * Get the count of recipients by status.
     */
    public function getRecipientsCountByStatus()
    {
        return $this->recipients()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }
}
