<?php

namespace App\Repositories;

use App\Models\Smtp;

class SmtpRepository extends BaseRepository
{
    public function __construct(Smtp $model)
    {
        parent::__construct($model);
    }

    public function updateWithMapping(int $id, array $data): bool
    {
        return parent::update($id, $this->mapping($data));
    }

    public function createWithMapping(array $data): Smtp
    {
        return $this->create($this->mapping($data));
    }

    public function updateStatus(int $action, array $ids): void
    {
        $ids = array_filter($ids, static fn ($id) => is_numeric($id));

        if (empty($ids)) {
            return;
        }

        match ($action) {
            0, 1 => $this->model
                ->whereIn('id', $ids)
                ->update(['active' => $action]),

            2 => $this->model
                ->whereIn('id', $ids)
                ->delete(),

            default => null,
        };
    }

    private function mapping(array $data): array
    {
        $mapped = collect($data)
            ->only($this->model->getFillable())
            ->map(function ($value, $key) {
                return match ($key) {
                    'port', 'timeout' => ! is_null($value) ? (int) $value : null,
                    'authentication' => match ($value) {
                        'no' => Smtp::AUTH_LOGIN,
                        'crammd5' => Smtp::AUTH_CRAM_MD5,
                        default => $value,
                    },
                    default => $value,
                };
            })
            ->toArray();

        if (
            array_key_exists('password', $mapped)
            && ($mapped['password'] === null || $mapped['password'] === '')
        ) {
            unset($mapped['password']);
        }

        return $mapped;
    }
}
