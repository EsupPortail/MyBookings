<?php

namespace App\Controller;

use App\Entity\Acl;
use App\Entity\Service;
use App\Entity\User;
use App\Repository\AclRepository;
use App\Repository\CategoryRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use App\Tools\UserTools;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ServiceController extends AbstractController
{

    public function __construct(private CategoryRepository $categoryRepository, private ServiceRepository $serviceRepository, private AclRepository $aclRepository, private UserRepository $userRepository)
    {
    }

    #[Route(path: '/api/service', name: 'get_user_sites', methods: ['GET'])]
    public function getUserSites(ServiceRepository $serviceRepository, AclRepository $aclRepository): JsonResponse
    {
        $acls =  $aclRepository->findBy(["user" => $this->getUser()]);
        $services = [];
        foreach ($acls as $acl) {
            $services[] = ['id' => $acl->getService()->getId(), 'title' => $acl->getService()->getTitle(), 'type' => $acl->getType()];
        }
        return new JsonResponse($services);
    }

    #[Route(path: '/api/service/all', name: 'get_all_sites', methods: ['GET'])]
    public function getAllSites(ServiceRepository $serviceRepository): JsonResponse
    {
        $services = $serviceRepository->findAll();
        $serviceSoftTable = [];
        foreach ($services as $service) {
            $serviceSoftTable[] = ['title' => $service->getTitle(), 'id' => $service->getId()];
        }
        return new JsonResponse($serviceSoftTable);
    }

    #[Route(path: '/api/service/{id}/users', name: 'get_site_users', methods: ['GET'])]
    public function getSiteUsers($id,AclRepository $aclRepository, UserTools $userTools): Response
    {
        $acls = $aclRepository->findBy(['service' => $id]);
        if($acls) {
            $users = [];
            foreach ($acls as $acl) {
                $user = $acl->getUser();
                if($acl->getType() !== 'ROLE_EXTERNAL') {
                    $users[] = ['id' => $acl->getId(), 'username' => $user->getDisplayUserName(), 'mail' => $user->getEmail(), 'role' => $acl->getType(), 'hashkey' => $userTools->encode($user->getUsername())];
                }
            }
            return new JsonResponse($users);
        }
        return new Response("Wrong ID", 400);
    }

    #[Route(path: '/api/service/{id}/catalogues', name: 'get_catalogues_site', methods: ['GET'])]
    public function getSiteCatalogues($id,ServiceRepository $serviceRepository): Response
    {
        $service = $serviceRepository->find($id);
        if($service) {
            $catalogueList = $service->getCatalogueResources();
            $catalogues = [];
            foreach ($catalogueList as $catalogue) {
                $catalogues[] = ['id' => $catalogue->getId(), 'title' => $catalogue->getTitle(), 'type' => ['id' => $catalogue->getType()->getId(), 'label' => $catalogue->getType()->getTitle()], 'image' => $catalogue->getImage(), 'service' => $service->getTitle(), 'nb' => sizeof($catalogue->getResource()->getValues())];
            }
            return new JsonResponse($catalogues);
        }
        return new Response("Wrong ID", 400);
    }

    #[Route(path: '/api/service/{id}/catalogues/type/{type}', name: 'get_catalogues_type_site', methods: ['GET'])]
    #[Route(path: '/api/service/{id}/catalogues/subtype/{type}', name: 'get_catalogues_subtype_site', methods: ['GET'])]
    public function getSiteCataloguesByType($id, $type, ServiceRepository $serviceRepository, Request $request): Response
    {
        $route = $request->attributes->get('_route');
        $service = $serviceRepository->find($id);
        $type = $this->categoryRepository->find($type);
        if ($service) {
            $catalogueList = $service->getCatalogueResources();
            $catalogues = [];
            foreach ($catalogueList as $catalogue) {
                if($route === 'get_catalogues_type_site') {
                    if ($catalogue->getType() === $type) {
                        $catalogues[] = ['id' => $catalogue->getId(), 'title' => $catalogue->getTitle(), 'type' => $catalogue->getType(), 'image' => $catalogue->getImage(), 'service' => $service->getTitle(), 'nb' => sizeof($catalogue->getResource()->getValues())];
                    }
                } else {
                    if ($catalogue->getSubType() === $type) {
                        $catalogues[] = ['id' => $catalogue->getId(), 'title' => $catalogue->getTitle(), 'type' => $catalogue->getType(), 'image' => $catalogue->getImage(), 'service' => $service->getTitle(), 'nb' => sizeof($catalogue->getResource()->getValues())];
                    }
                }
            }
            return new JsonResponse($catalogues);
        }
        return new Response('Le service n\'existe pas', 400);
    }


    #[Route(path: '/api/new/service', name: 'create_site', methods: ['POST'])]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ADMIN_RESSOURCERIE")'))]
    public function createSite(Request $request, ServiceRepository $serviceRepository, UserController $userController, UserTools $userTools): Response
    {
        $parameters = $request->request->all();
        $service = new Service();
        $service->setTitle($parameters['title']);
        $service->setType($parameters['type']);
        try {
            $serviceRepository->add($service, true);
        } catch (ORMException $e) {
            echo 'ORMException : ' . $e->getMessage();
        }
        $admins = json_decode($parameters['admin']);
        foreach ($admins as $admin) {
            $userController->addRoleUser($userTools->decode($admin->value), 'ROLE_ADMINSITE', $service->getId());
        }
        return new Response($service->getId());
    }

    #[Route(path: '/api/service/{id}', name: 'remove_site', methods: ['DELETE'])]
    #[IsGranted('editService', 'id')]
    public function removeSite($id): Response
    {
        $service = $this->serviceRepository->find($id);
        if($service) {
            $this->deleteLinkedAcl($service);
            try {
                $this->serviceRepository->remove($service, true);
            } catch (ORMException $e) {
                echo 'ORMException : ' . $e->getMessage();
            }
        }
        return new Response(true, 200);
    }

    private function deleteLinkedAcl($service): void
    {
        $linkAcl = $this->aclRepository->findBy(['service' => $service]);
        if(!empty($linkAcl)) {
            foreach ($linkAcl as $acl) {
                $this->aclRepository->remove($acl);
            }
        }

    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route(path: '/api/service/{id}/users/add', name: 'post_site_external_users', methods: ['post'])]
    #[IsGranted('editService', 'id')]
    public function postSiteUsers(Service $id, Request $request): Response
    {
        $data = $request->request->all();
        $user = new User();
        $user->setEmail($data['email']);
        $user->setUsername('userext');
        $user->setDisplayUserName($data['name']);
        $this->userRepository->add($user, true);
        $user->setUsername('userext'.$user->getId());
        $acl = new Acl();
        $acl->setUser($user);
        $acl->setService($id);
        $acl->setType('ROLE_EXTERNAL');

        $this->aclRepository->add($acl, true);

        return new Response($user->getId());
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route(path: '/api/service/{service}/users/{user}', name: 'remove_site_external_user', methods: ['DELETE'])]
    #[IsGranted('editService', 'service')]
    public function removeSiteExternalUser(Service $service, User $user): Response
    {
        $acl = $this->aclRepository->findOneBy(['user' => $user, 'service' => $service]);
        if($acl) {
            $acls = $this->aclRepository->findBy(['user' => $user]);
            foreach ($acls as $acl) {
                $this->aclRepository->remove($acl);
            }

            $this->userRepository->remove($user, true);
        }

        return new Response(true, 200);
    }
}
