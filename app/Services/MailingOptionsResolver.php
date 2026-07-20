<?php

namespace App\Services;

use App\DTO\MailingOptions;
use App\Helpers\SettingsHelper;

class MailingOptionsResolver
{
    public function resolve(): MailingOptions
    {
        $settings = SettingsHelper::getInstance();

        return MailingOptions::fromValues(
            randomOrder: (int) $settings->getValueForKey('RANDOM_SEND') === 1,
            limitEnabled: (int) $settings->getValueForKey('LIMIT_SEND') === 1,
            limit: (int) $settings->getValueForKey('LIMIT_NUMBER'),
            intervalType: (string) $settings->getValueForKey('INTERVAL_TYPE'),
            intervalNumber: (int) $settings->getValueForKey('INTERVAL_NUMBER'),
        );
    }
}
