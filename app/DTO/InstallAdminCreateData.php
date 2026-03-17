<?php

namespace App\DTO;

class InstallAdminCreateData
{
    public function __construct(
        public readonly string $name,
        public readonly string $login,
        public readonly string $role,
        public readonly string $password,
    ) {
    }
}
