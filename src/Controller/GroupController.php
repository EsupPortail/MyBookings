<?php

namespace App\Controller;

use App\Entity\Group;
use App\Repository\GroupRepository;
use App\Service\GroupServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GroupController extends AbstractController
{
    public function __construct(private readonly GroupServiceInterface $groupService, private readonly GroupRepository $groupRepository)
    { }

    #[Route(path: '/api/groups/{id}/users', name: 'load_group_users', methods: ['GET'])]
    public function loadGroupUsers(Group $group): Response
    {
        if ($group->getQuery() !== '') {
            $this->groupService->updateGroups($group->getId());
        }
        $users = json_decode($group->getUsers());
        return new JsonResponse($users, 200);
    }
}
