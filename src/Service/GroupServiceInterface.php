<?php

namespace App\Service;

use App\Entity\Group;
use phpDocumentor\Reflection\Types\Integer;

interface GroupServiceInterface
{
    public function findUsers(Group $group): array;

    public function updateGroups(Integer $id = null): bool;
}