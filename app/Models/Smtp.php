<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class Smtp extends Model
{
    use StaticTableName;

    public const AUTH_LOGIN = 'login';

    public const AUTH_PLAIN = 'plain';

    public const AUTH_CRAM_MD5 = 'cram-md5';

    public const AUTHENTICATION_METHODS = [
        self::AUTH_LOGIN,
        self::AUTH_PLAIN,
        self::AUTH_CRAM_MD5,
    ];

    public const SECURE_NONE = 'no';

    public const SECURE_SSL = 'ssl';

    public const SECURE_TLS = 'tls';

    public const SECURE_METHODS = [
        self::SECURE_NONE,
        self::SECURE_SSL,
        self::SECURE_TLS,
    ];

    protected $table = 'smtp';

    protected $fillable = [
        'host',
        'email',
        'username',
        'password',
        'port',
        'authentication',
        'secure',
        'timeout',
        'active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'port' => 'integer',
        'timeout' => 'integer',
        'active' => 'boolean',
    ];

    public function phpMailerAuthType(): ?string
    {
        return match ($this->authentication) {
            self::AUTH_LOGIN, 'no' => 'LOGIN',
            self::AUTH_PLAIN => 'PLAIN',
            self::AUTH_CRAM_MD5, 'crammd5' => 'CRAM-MD5',
            default => null,
        };
    }
}
