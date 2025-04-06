<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class ExpensifyLogin extends Model
{
    protected $fillable = [
        'slack_user_id',
        'partner_id',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function password(): Attribute
    {
        return Attribute::make(
            get: function (string $value) {
                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException $e) {
                    // Log the error but don't expose it to the user
                    report($e);
                    return '';
                }
            },
            set: function (string $value) {
                return Crypt::encryptString($value);
            },
        );
    }

    public static function isLoggedIn(string $slackUserId): bool
    {
        return static::where('slack_user_id', $slackUserId)->exists();
    }
}
