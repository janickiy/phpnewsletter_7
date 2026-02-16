<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * @param array $data
     * @return User
     */
    public function createWithMapping(array $data): User
    {
        return $this->create($this->mapping($data));
    }

    public function updateWithMapping(int $id, array $data): bool
    {
        return $this->update($id, $this->mapping($data));
    }

    private function mapping(array $data): array
    {
        return collect($data)
            ->merge([
                'role' => $data['role'] ?? null,
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
            ])
            ->only($this->model->getFillable())
            ->map(function ($value, $key) {
                if ($key === 'password' && !is_null($value)) {
                    return  Hash::make($value);
                }
                return $value;
            })
            ->all();
    }
}
