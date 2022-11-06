<?php

namespace App\Helpers;

class SettingsHelper
{
    /**
     * @param string $key
     * @return string
     */
    public static function getSetting($key = '')
    {
        $setting = Settings::where('name', $key)->first();

        if ($setting) {
            return $setting->value;
        } else {
            return '';
        }
    }
}
