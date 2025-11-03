<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmtpConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password',
        'from_address',
        'from_name',
        'encryption',
        'is_active',
        'is_default',
        'description'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'port' => 'integer'
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Encrypt SMTP password before saving.
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = encrypt($value);
    }

    /**
     * Decrypt SMTP password when retrieving.
     */
    public function getPasswordAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }
        
        try {
            return decrypt($value);
        } catch (\Exception $e) {
            // If decryption fails, assume it's already plaintext (for existing data)
            return $value;
        }
    }

    /**
     * Get the default SMTP configuration
     */
    public static function getDefault()
    {
        return self::where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * Get all active SMTP configurations
     */
    public static function getActive()
    {
        return self::where('is_active', true)->orderBy('is_default', 'desc')->get();
    }

    /**
     * Set as default configuration
     */
    public function setAsDefault()
    {
        // Remove default from all other configurations
        self::where('is_default', true)->update(['is_default' => false]);
        
        // Set this as default
        $this->update(['is_default' => true, 'is_active' => true]);
    }

    /**
     * Get configuration array for Laravel Mail
     */
    public function getMailConfig()
    {
        // Get raw password for mail config (decrypted)
        $password = $this->password;
        
        return [
            'driver' => 'smtp',
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $password,
            'encryption' => $this->encryption,
            'from' => [
                'address' => $this->from_address,
                'name' => $this->from_name,
            ],
        ];
    }
}