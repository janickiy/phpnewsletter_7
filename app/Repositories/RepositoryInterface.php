<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface RepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Model;

    public function create(array $data): Builder|Model;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function truncate(): void;
}
