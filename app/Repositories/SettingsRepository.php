<?php

namespace App\Repositories;

use App\Helpers\SettingsHelper;
use App\Helpers\StringHelper;
use App\Models\CustomHeaders;
use App\Models\Settings;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SettingsRepository extends BaseRepository
{
    public function __construct(
        Settings $model,
        private readonly CustomHeaders $customHeaders,
    ) {
        parent::__construct($model);
    }

    public function setSettings(array $data): void
    {
        $settings = $this->normalizeSettings($data);
        $headers = $this->normalizeHeaders($data);

        DB::transaction(function () use ($settings, $headers): void {
            foreach ($settings as $name => $value) {
                $this->model->newQuery()->updateOrCreate(
                    ['name' => $name],
                    ['value' => $value],
                );
            }

            $this->customHeaders->newQuery()->delete();

            if ($headers !== []) {
                $this->customHeaders->newQuery()->insert($headers);
            }
        });

        SettingsHelper::refresh();
    }

    /**
     * Keep persistence limited to known settings and normalize checkbox values.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeSettings(array $data): array
    {
        $settings = Arr::only($data, Settings::EDITABLE_KEYS);

        foreach ($settings as $key => $value) {
            $settings[$key] = $value ?? '';
        }

        foreach (Settings::BOOLEAN_KEYS as $key) {
            $settings[$key] = ! empty($data[$key]) ? 1 : 0;
        }

        if (array_key_exists('URL', $settings) && trim((string) $settings['URL']) === '') {
            $settings['URL'] = StringHelper::getUrl();
        }

        return $settings;
    }

    /**
     * Build a clean, de-duplicated list of RFC-compatible custom headers.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array{name: string, value: string, created_at: mixed, updated_at: mixed}>
     */
    private function normalizeHeaders(array $data): array
    {
        $names = array_values(Arr::wrap($data['header_name'] ?? []));
        $values = array_values(Arr::wrap($data['header_value'] ?? []));
        $headers = [];

        foreach ($names as $index => $headerName) {
            $name = trim((string) $headerName);
            $value = trim((string) ($values[$index] ?? ''));

            if (
                $name === ''
                || $value === ''
                || preg_match('/^[A-Za-z][A-Za-z0-9-]*$/', $name) !== 1
            ) {
                continue;
            }

            $headers[strtolower($name)] = [
                'name' => $name,
                'value' => preg_replace('/[\r\n]+/', ' ', $value) ?? '',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return array_values($headers);
    }
}
