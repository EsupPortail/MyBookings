<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use App\Tools\UserTools;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly GroupRepository    $groupRepository,
        private readonly UserRepository      $userRepository,
        private readonly array $apiHashkey,
        private readonly UserTools $userTools,
        private readonly string $ressourcerieUsernames,
        private readonly string $administrators,
        private readonly RemoteService $remoteService,
        private readonly SerializerInterface $serializerInterface,
        #[Autowire('%auth_type%')]
        private readonly string $authType,
        #[Autowire('%platform_mode%')]
        private readonly string $platformMode,

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


        try {
            $localUser = $this->remoteService->findUser($username);
        } catch (ClientExceptionInterface $e) {
            if($this->platformMode === 'demo' && strtoupper($this->authType) === 'SHIBBOLETH') {
                    return $this->createDemoUser($username);
            }
        }
        return $this->serializerInterface->deserialize($localUser, User::class, 'json');
    }

    private function setRolesFromGroups(User $user): User
    {
        $roles = array_filter($user->getRoles(), function ($value) {
           return !str_starts_with($value, "ROLE_GROUP_") && !str_starts_with($value, "ROLE_ADMIN") && !str_starts_with($value, "ROLE_SWITCHED_USER");
        });

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

        $user->setRoles([...$roles]);

        return $user;
    }

    public function searchUsers(string $search, bool $admin = false): array
    {
        if (preg_match("/[A-z\- ']/",$search)===0)
            return [];

        $externalUsers = [];
        if($this->platformMode !== 'demo') {
            $users = json_decode($this->remoteService->searchUsers($search));
            $externalUsers = $this->userTools->searchExternalUser($search);

            if(!$admin) {
                foreach ($externalUsers as $key => $external) {
                    $externalUsers[$key]['value'] = $this->userTools->encode($external['uid']);
                    unset($externalUsers[$key]['uid']);
                }
                foreach($users as $user) {
                    $user->value = $this->userTools->encode($user->uid);
                    unset($user->uid);
                }
            }
        } else {
            $users = $this->userTools->searchInternalUser($search, $admin);
        }



        return array_merge($users, $externalUsers);
    }

    public function createDemoUser($username): User
    {
        $group = $this->groupRepository->findOneBy(['title' => 'Utilisateurs demo']);
        $users = json_decode($group->getUsers());
        if(!in_array($username, $users)) {
            $group->setUsers(json_encode([...$users, $username]));
            $this->groupRepository->save($group, true);
        }
        $user = new User();
        $user->setUsername($username);
        $user->setDisplayUserName($_SERVER['HTTP_DISPLAYNAME']);
        $user->setEmail($_SERVER['HTTP_MAIL']);
        $user->setRoles(["ROLE_USER"]);
        // Set roles based on entity
        if (preg_match('/@([a-zA-Z0-9_-]+)\./', $_SERVER['HTTP_EPPN'], $matches)) {
            $entityRole = strtoupper($matches[1]);
            $user->setRoles(["ROLE_USER", "ROLE_ENTITY_$entityRole"]);
        }
        return $user;
    }
}