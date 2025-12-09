<?php

namespace App\Service\Uca;

use App\Entity\User;
use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use App\Service\AesCipher;
use App\Service\UserServiceInterface;
use App\Tools\UserTools;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use phpDocumentor\Reflection\Types\Boolean;

class DemoUserService implements UserServiceInterface
{
    public function __construct(
        private readonly GroupRepository    $groupRepository,
        private readonly UserRepository      $userRepository,
        private readonly array $apiHashkey,
        private readonly UserTools $userTools,
        private readonly string $ressourcerieUsernames,
        private readonly string $administrators,
    ) {}

    public function retrieveUser(string $identifier, bool $isSwitchedUser = false): ?User
    {
        $user = $this->userRepository->findOneBy(["username"=>$identifier]);

        if (!$user)
            $user = $this->createUser($identifier);

        $user = $this->setRolesFromGroups($user);
        if($isSwitchedUser) {
            $roles = $user->getRoles();
            $user->setRoles(array_merge($roles, ["ROLE_SWITCHED_USER"]));
        }
        $keys = $this->apiHashkey["MYBOOKINGS"];
        $user->setEncryptedUsername(urlencode(AesCipher::encrypt($identifier, $keys["hashKey"], $keys["initVector"])));

        if(sizeof($user->getRoles()) >1) {
            try {
                $this->userRepository->add($user, true);
            } catch (OptimisticLockException|ORMException $e) {
                echo 'ORMException : '.$e->getMessage();
            }
            return $user;
        }
        return null;
    }

    public function createUser($username): User|null {
        if($username) {
            $newUser = new User();
            $newUser->setEmail($username);
            $newUser->setUsername($username);
            $newUser->setDisplayUserName($username);
            $newUser->setRoles($this->setRolesFromStatus(0));
            return $newUser;
        }
        return null;
    }

    private function setRolesFromStatus($status): array
    {
        $roles=["ROLE_USER"];
        switch ($status) {
            case "9" :
            case "10" :
                $roles[] = "ROLE_EMPLOYEE";
                break;
            case "0" :
            case "1" :
            case "2" :
                $roles[] = "ROLE_STUDENT";
                break;
            case "5" :
                $roles[] = "ROLE_GUEST";
                break;
            default :
                $roles = ["PUBLIC_ACCESS"];
        }
        return $roles;
    }

    private function setRolesFromGroups(User $user): User
    {
        $roles = [];

        $groups = $this->groupRepository->findAll();
        foreach ($groups as $group) {
            if($group->getUsers() !== null) {
                if(str_contains($group->getUsers(), $user->getUserIdentifier())) {
                    $roles[] = "ROLE_GROUP_".$group->getId();
                }
            }
        }

        foreach ($user->getAcls() as $acl) {
            $roles[] = $acl->getType().'_'.$acl->getService()->getId();
        }

        $admins = json_decode($this->administrators);
        if (in_array($user->getUserIdentifier(), $admins)) {
            $roles[] = "ROLE_ADMIN";
            $roles[] = "ROLE_CAN_SWITCH";
        }

        // Am I an administrator for Ressourcerie
        $ressourcerieUsernames = json_decode($this->ressourcerieUsernames);
        if (is_array($ressourcerieUsernames) && in_array($user->getUserIdentifier(), $ressourcerieUsernames))
            $roles[] = 'ROLE_ADMIN_RESSOURCERIE';
        $user->setRoles($roles);

        return $user;
    }

    public function searchUsers(string $search, bool $admin = false): array
    {
        if (preg_match("/[A-z\- ']/",$search)===0)
            return [];

        $users = $this->userRepository->findUserBy($search);
        $rsltUsers = [];
        foreach($users as $key => $user) {
            $rsltUsers[$key] = new \stdClass();
            $rsltUsers[$key]->label = $user->getDisplayUserName();
            $rsltUsers[$key]->email = $user->getEmail();
            $rsltUsers[$key]->value = $this->userTools->encode($user->getDisplayUserName());
        }

        return $rsltUsers;
    }

}