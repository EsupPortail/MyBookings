<?php

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiPlatform\Dto\ActiveUsersCount;
use App\Repository\UserRepository;

class ActiveUsersCountProvider implements ProviderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ActiveUsersCount
    {
        $since = new \DateTimeImmutable('-10 minutes');

        $dto = new ActiveUsersCount();
        $dto->count = $this->userRepository->countActiveUsersSince($since);
        $dto->since = $since->format('c');

        return $dto;
    }
}

