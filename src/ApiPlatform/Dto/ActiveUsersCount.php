<?php

namespace App\ApiPlatform\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\ApiPlatform\State\ActiveUsersCountProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/users/active-count',
            description: 'Get the count of connected users in the last 10 minutes',
            security: "is_granted('ROLE_ADMIN')",
            provider: ActiveUsersCountProvider::class,
        )
    ]
)]
class ActiveUsersCount
{
    public int $count;
    public string $since;
}