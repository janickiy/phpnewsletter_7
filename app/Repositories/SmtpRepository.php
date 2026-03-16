<?php

namespace App\Repositories;

use App\Models\Smtp;

class SmtpRepository extends BaseRepository
{
    public function __construct(Smtp $model)
    {
        parent::__construct($model);
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateWithMapping(int $id, array $data): bool
    {
        return parent::update($id, $this->mapping($data));
    }

    /**
     * @param array $data
     * @return Smtp
     */
    public function createWithMapping(array $data): Smtp
    {
        return $this->create($this->mapping($data));
    }

    /**
     * @param int $action
     * @param array $ids
     * @return void
     */
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

    /**
     * @param array $data
     * @return array
     */
    private function mapping(array $data): array
    {
        $mapped = collect($data)
            ->only($this->model->getFillable())
            ->map(function ($value, $key) {
                return match ($key) {
                    'port', 'timeout' => !is_null($value) ? (int) $value : null,
                    default => $value,
                };
            })
            ->toArray();

        if (array_key_exists('password', $mapped) && empty($mapped['password'])) {
            unset($mapped['password']);
        }

        return $mapped;
    }
}
