<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use StaticTableName;

    public const BOOLEAN_KEYS = [
        'REQUIRE_SUB_CONFIRMATION',
        'SHOW_UNSUBSCRIBE_LINK',
        'REQUEST_REPLY',
        'NEW_SUBSCRIBER_NOTIFY',
        'LIMIT_SEND',
        'REMOVE_SUBSCRIBER',
        'RANDOM_SEND',
        'RENDOM_REPLACEMENT_SUBJECT',
        'RANDOM_REPLACEMENT_BODY',
    ];

    public const EDITABLE_KEYS = [
        'EMAIL',
        'FROM',
        'RETURN_PATH',
        'LIST_OWNER',
        'ORGANIZATION',
        'SUBJECT_TEXT_CONFIRM',
        'TEXT_CONFIRMATION',
        'UNSUBLINK',
        'INTERVAL_NUMBER',
        'INTERVAL_TYPE',
        'LIMIT_NUMBER',
        'SLEEP',
        'DAYS_FOR_REMOVE_SUBSCRIBER',
        'PRECEDENCE',
        'CHARSET',
        'CONTENT_TYPE',
        'HOW_TO_SEND',
        'SENDMAIL_PATH',
        'URL',
        ...self::BOOLEAN_KEYS,
    ];

    protected $table = 'settings';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'value',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    public function setNameAttribute(string $name): void
    {
        $this->attributes['name'] = str_replace(' ', '_', strtoupper(trim($name)));
    }
}
