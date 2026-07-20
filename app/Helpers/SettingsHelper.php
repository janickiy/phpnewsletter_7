<?php

namespace App\Helpers;

use App\Models\Settings;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingsHelper
{
    public const CACHE_KEY = 'settings';

    private static ?self $instance = null;

    private static ?Collection $settings = null;

    private function __construct() {}

    private function __clone() {}

    public static function getInstance(): self
    {
        return self::$instance ??= new self;
    }

    /**
     * Return all settings keyed by their names.
     *
     * @return Collection<string, mixed>
     */
    public static function all(): Collection
    {
        return self::settings();
    }

    /**
     * @return Collection<string, mixed>
     */
    private static function settings(): Collection
    {
        return self::$settings ??= self::loadSettingsFromCache();
    }

    /**
     * @return Collection<string, mixed>
     */
    private static function loadSettingsFromCache(): Collection
    {
        try {
            return Cache::remember(
                self::CACHE_KEY,
                180,
                fn (): Collection => Settings::query()->pluck('value', 'name'),
            );
        } catch (QueryException) {
            return collect();
        }
    }

    public static function getValueForKey(string $name, mixed $default = false): mixed
    {
        return self::settings()->get($name, $default);
    }

    public static function get(string $key, mixed $default = false): mixed
    {
        return self::getValueForKey($key, $default);
    }

    public static function has(string $key): bool
    {
        return self::settings()->has($key);
    }

    /**
     * Forget cached settings and immediately load the current values.
     */
    public static function refresh(): void
    {
        self::cacheClear();
        self::settings();
    }

    public static function cacheClear(bool $reload = false): bool
    {
        $result = Cache::forget(self::CACHE_KEY);
        self::$settings = null;

        if ($reload) {
            self::settings();
        }

        return $result;
    }
}
