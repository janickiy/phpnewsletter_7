<?php

namespace App\Helpers;

use App\Models\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

class SettingsHelper
{
    public const CACHE_KEY = 'settings';

    private static $instance;

    private $settings;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
            self::$instance->loadSettings();
        }

        return self::$instance;
    }

    /**
     * @return array
     */
    private function loadSettings(): mixed
    {
        $this->settings = self::loadSettingsFromCache();

        return $this->settings;
    }

    /**
     * @param bool $cache
     * @return array
     */
    private static function loadSettingsFromCache(bool $cache = false): Collection
    {
        if ($cache === true) {
            return Cache::remember(self::CACHE_KEY, 180, function () {
                try {
                    $settings = Settings::all();
                } catch (QueryException $e) {
                    return collect();
                }

                if ($settings === null) {
                    return collect();
                }

                return  $settings->pluck('value', 'name');
            });
        } else {
            $settings = Settings::all();

            return $settings->pluck('value', 'name');
        }
    }

    /**
     * @param string $name
     * @param bool $default
     * @return bool|mixed
     */
    public static function getValueForKey(string $name, bool $default = false): mixed
    {
        $settings = SettingsHelper::getInstance()->settings;

        return $settings[$name] ?? $default;
    }

    /**
     * @param string $key
     * @param bool $default
     * @return bool|mixed
     */
    public static function get(string $key, bool $default = false): mixed
    {
        return self::getValueForKey($key, $default);
    }

    /**
     * @param bool $key
     * @return bool
     */
    public static function has(bool $key): bool
    {
        return isset(self::getInstance()->settings[$key]);
    }

    /**
     * @param bool $reload
     * @return bool
     */
    public static function cacheClear(bool $reload = false)
    {
        $result = Cache::forget(self::CACHE_KEY);

        if ($result && $reload) {
            self::loadSettingsFromCache();
        }

        return $result;
    }
}
