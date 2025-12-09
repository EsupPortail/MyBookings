<?php

namespace App\Service;

use App\Entity\Group;
use App\Repository\GroupRepository;

class GroupService implements GroupServiceInterface
{
    public function __construct(private readonly GroupRepository $groupRepository, private readonly RemoteService $remoteService)
    {
    }

    public function findUsers(Group $group): array
    {
        if($group->getProvider() !== "db")
            return json_decode($this->remoteService->searchUsersFromProvider(['provider' => $group->getProvider(), 'query' => $group->getQuery()]));

        return [];
    }

    public function updateGroups($id = null): bool
    {
        $groups=[];
        if ($id) {
            $groups[] = $this->groupRepository->findOneBy(['id' => $id]);
        } else {
            $groups = $this->groupRepository->findAll();
        }
        foreach ($groups as $key=>$group) {
            if($group->getQuery() !== '') {
                $users = $this->findUsers($group);
                $usernames = array_map(fn($user) => $user->username, $users);
                $group->setUsers(json_encode($usernames));
                $this->groupRepository->save($group,($this->getLastRequestableGroup($groups)===$key));
            }
        }
        return true;
    }

    private function getLastRequestableGroup($groups): int|string|null
    {
        $lastGroup = null;
        foreach ($groups as $key=>$group) {
            if($group->getQuery() !== '') {
                $lastGroup =  $key;
            }
        }

        return $lastGroup;
    }
}