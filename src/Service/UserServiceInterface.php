<?php

namespace App\Service;

use App\Entity\User;
use phpDocumentor\Reflection\Types\Boolean;

interface UserServiceInterface
{
    public function retrieveUser(string $identifier, bool $isSwitchedUser): ?User;

    public function createUser($username): User|null;

    public function searchUsers(string $search, bool $admin): array;
}