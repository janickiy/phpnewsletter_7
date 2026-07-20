<?php

namespace App\Enums;

use App\Models\User;

enum AjaxAction: string
{
    case StartUpdate = 'start_update';
    case AlertUpdate = 'alert_update';
    case RemoveSchedule = 'remove_schedule';
    case ChangeLanguage = 'change_lng';
    case RemoveAttach = 'remove_attach';
    case SendTestEmail = 'send_test_email';
    case SendOut = 'send_out';
    case CountSend = 'count_send';
    case LogOnline = 'log_online';
    case StartMailing = 'start_mailing';
    case GetCategories = 'get_categories';
    case Process = 'process';

    /**
     * Determine whether this action may be called without an authenticated user.
     */
    public function isPublic(): bool
    {
        return $this === self::ChangeLanguage;
    }

    /**
     * Determine whether the given user may execute this action.
     */
    public function isAllowedFor(?User $user): bool
    {
        if ($this->isPublic()) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $this !== self::StartUpdate || $user->role === User::ROLE_ADMIN;
    }
}
