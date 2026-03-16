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
        $mapped = collect($data)
            ->only($this->model->getFillable())
            ->toArray();

        if (empty($mapped['password'])) {
            unset($mapped['password']);
        } else {
            $mapped['password'] = Hash::make($mapped['password']);
        }

        return $mapped;
    }
}
