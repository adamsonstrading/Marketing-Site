<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sender extends Model
{
    protected $fillable = [
        'name',
        'email',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'from_name',
        'from_address',
    ];

    protected $casts = [
        'smtp_port' => 'integer',
    ];

    protected $hidden = [
        'smtp_password',
    ];

    /**
     * Get the campaigns for the sender.
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Encrypt SMTP password before saving.
     */
    public function setSmtpPasswordAttribute($value)
    {
        $this->attributes['smtp_password'] = encrypt($value);
    }

    /**
     * Decrypt SMTP password when retrieving.
     */
    public function getSmtpPasswordAttribute($value)
    {
        return decrypt($value);
    }
}
