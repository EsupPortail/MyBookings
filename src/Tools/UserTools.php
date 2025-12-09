<?php

namespace App\Tools;

use App\Repository\AclRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class UserTools
{
    public function __construct(private readonly RequestStack $requestStack, private readonly UserRepository $userRepository, private readonly AclRepository $aclRepository)
    {}

    public function encode(String $uid): string
    {
        $hash = hash('sha256',$uid);

        $session = $this->requestStack->getSession();
        if (!$session->get($hash))
            $session->set($hash,$uid);

        return $hash;
    }

    public function decode(String $text): string|null
    {
        $session = $this->requestStack->getSession();
        return $session->get($text);
    }

    public function searchExternalUser($args): array
    {
        $userArray = [];
        $acls = $this->aclRepository->findBy(['type' => 'ROLE_EXTERNAL']);
        foreach ($acls as $acl) {
            if(str_contains(strtolower($acl->getUser()->getDisplayUserName()),strtolower($args))) {
                $userArray[] = [
                    'uid' => $acl->getUser()->getUserIdentifier(),
                    'label' => $acl->getUser()->getDisplayUserName(),
                    'displayName' => $acl->getUser()->getDisplayUserName(),
                    'mail' => $acl->getUser()->getEmail()
                ];
            }
        }
        return $userArray;
    }

    public function searchInternalUser($args, $isAdmin): array
    {
        $userArray = [];
        $users = $this->userRepository->findUserBy($args);
        foreach ($users as $user) {
            if(str_contains(strtolower($user->getDisplayUserName()),strtolower($args))) {
                $userArray[] = [
                    'uid' => !$isAdmin ? null : $user->getUserIdentifier(),
                    'value' => !$isAdmin ? $this->encode($user->getUserIdentifier()) : null,
                    'label' => $user->getDisplayUserName(),
                    'displayName' => $user->getDisplayUserName(),
                    'mail' => $user->getEmail()
                ];
            }
        }
        return $userArray;
    }
}