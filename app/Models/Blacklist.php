<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blacklist extends Model
{
    use HasFactory;

    protected $table = 'blacklist';

    protected $fillable = [
        'email',
        'domain',
        'reason',
    ];

    /**
     * Check if an email address is blacklisted
     */
    public static function isBlacklisted(string $email): bool
    {
        // Extract domain from email
        $domain = substr(strrchr($email, "@"), 1);

        // Check if specific email is blacklisted
        if (self::where('email', $email)->exists()) {
            return true;
        }

        // Check if domain is blacklisted
        if (self::where('domain', $domain)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Add email to blacklist
     */
    public static function addEmail(string $email, ?string $reason = null): self
    {
        return self::updateOrCreate(
            ['email' => $email],
            ['reason' => $reason]
        );
    }

    /**
     * Add domain to blacklist
     */
    public static function addDomain(string $domain, ?string $reason = null): self
    {
        return self::create([
            'domain' => $domain,
            'reason' => $reason,
        ]);
    }

    /**
     * Remove from blacklist
     */
    public static function remove(string $email = null, string $domain = null): bool
    {
        $query = self::query();
        
        if ($email) {
            $query->where('email', $email);
        }
        
        if ($domain) {
            $query->orWhere('domain', $domain);
        }

        return $query->delete() > 0;
    }
}
