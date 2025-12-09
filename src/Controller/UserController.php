<?php

namespace App\Controller;

use App\Entity\Acl;
use App\Entity\User;
use App\Repository\AclRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use App\Service\UserServiceInterface;
use App\Tools\UserTools;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository    $userRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly AclRepository     $aclRepository,
        private readonly UserServiceInterface       $userService,
        private readonly UserTools         $userTools,
    ){}

    #[Route(path: '/api/user/me', name: 'get_user_infos', methods: ['GET'])]
    public function getUserInfos(): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['username' => $this->getUser()->getUserIdentifier()]);
        $userInformations = ['id' => $user->getId(), 'username' => $user->getUsername(), 'displayName' => $user->getDisplayUserName(), 'mail' => $user->getEmail()];
        return new JsonResponse($userInformations);
    }
    #[Route(path: '/api/user/roles', name: 'get_user_roles', methods: ['GET'])]
    public function getUserRoles(): JsonResponse
    {
        $switchedUser = false;
        in_array('ROLE_SWITCHED_USER', $this->getUser()->getRoles()) ? $switchedUser = true : $switchedUser = false;
        $user = $this->userService->retrieveUser($this->getUser()->getUserIdentifier(), $switchedUser);

        return new JsonResponse($user->getRoles());
    }

    #[Route(path: '/api/user/roles/{id}', name: 'remove_user_roles', methods: ['DELETE'])]
    #[IsGranted('moderateService', null, 'Accès refusé', 401)]
    public function removeRoleUserToService($id): Response
    {
        $acl = $this->aclRepository->find($id);
        $this->aclRepository->remove($acl, true);
        return new Response(true);
    }

    #[Route(path: '/api/user/roles', name: 'add_user_roles', methods: ['POST'])]
    #[IsGranted('moderateService', null, 'Accès refusé', 401)]
    public function addRoleUserToService(Request $request): Response
    {
        $parameters = $request->request->all();
        $idRole = $this->addRoleUser($this->userTools->decode($parameters['user']), $parameters['ROLE'], $parameters['site']);
        return new Response($idRole);
    }

    public function addRoleUser($username, $role, $siteId): int|null
    {
        $user = $this->userRepository->findOneBy(['username' => $username]);
        $site = $this->serviceRepository->find($siteId);
        if($user === null) {

            $user = $this->userService->createUser($username);
            if (is_null($user))
                return null;

            try {
                $this->userRepository->add($user, true);
            } catch (ORMException $e) {
                echo 'ORMException : '.$e->getMessage();
            }
        }
        $acl = $this->aclRepository->findOneBy(['user' => $user, 'service' => $site]);
        if($acl === null) {
            $acl = new Acl();
            $acl->setService($site);
            $acl->setUser($user);
        }

        $acl->setType($role);
        try {
            $this->aclRepository->add($acl, true);
        } catch (ORMException $e) {
            echo 'ORMException : '.$e->getMessage();
        }
        return $acl->getId();
    }

    #[Route(path: '/api/user/search', name: 'search_user', methods: ['GET'])]
    public function searchUser(Request $request): JsonResponse
    {
        return new JsonResponse($this->userService->searchUsers($request->query->get('query')));
    }

    #[Route(path: '/api/user/search/admin', name: 'search_user_admin', methods: ['GET'])]
    #[IsGranted('moderateService', null, 'Accès refusé', 401)]
    public function searchUserAdmin(Request $request): JsonResponse
    {
        return new JsonResponse($this->userService->searchUsers($request->query->get('query'), true));
    }

    #[Route(path: '/api/user/search/all', name: 'search_all_user', methods: ['GET'])]
    public function searchAllUser(Request $request): JsonResponse
    {
        return new JsonResponse($this->userService->searchUsers($request->query->get('query')));
    }
}
