<?php

namespace App\Controller;

use App\Entity\Provision;
use App\Tools\CatalogTool;
use App\Entity\CatalogueResource;
use App\Repository\AclRepository;
use App\Repository\GroupRepository;
use App\Repository\BookingRepository;
use App\Repository\ServiceRepository;
use App\Repository\ActuatorRepository;
use App\Repository\CategoryRepository;
use App\Repository\WorkflowRepository;
use App\Repository\ProvisionRepository;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\Workflow\Registry;
use App\Repository\WorkflowLogRepository;
use Doctrine\ORM\OptimisticLockException;
use App\Repository\LocalizationRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\CatalogueResourceRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



class ResourceCatalogueController extends AbstractController
{

    public function __construct(
        private CatalogueResourceRepository $catalogueResourceRepository,
        private ServiceRepository $serviceRepository,
        private AclRepository $aclRepository,
        private ProvisionRepository $provisionRepository,
        private CategoryRepository $categoryRepository,
        private GroupRepository $groupRepository,
        private ActuatorRepository $actuatorRepository,
        private BookingRepository $bookingRepository,
        private WorkflowLogRepository $workflowLogRepository,
        private LocalizationRepository $localizationRepository,
        #[Autowire('%platform_mode%')] private readonly string $platformMode,
        private Registry $workflows,
    ){}

    /**
     * @throws \Exception
     */
    #[Route(path: '/api/catalogue', name: 'add_catalogue', methods: ['POST'])]
    #[IsGranted('editService', subject: new Expression('request.request.get("service")'), statusCode: Response::HTTP_FORBIDDEN)]
    public function addCatalogue(Request $request, ResourceController $resourceController, CatalogTool $catalogTool): Response
    {
        $parameters = $request->request->all();
        $image = $request->files->get('image');
        $service = $this->serviceRepository->find($parameters['service']);
        $catalogue = new CatalogueResource();
        $catalogue->setTitle($parameters['title']);
        $catalogue->setDescription($parameters['description']);
        $catalogue->setType($this->categoryRepository->find($parameters['type']));
        $subtype = isset($parameters['subType']) ? $this->categoryRepository->find($parameters['subType']) : null;
        if (!empty($subtype)) {
            $catalogue->setSubType($subtype);
            $catalogue->setView($subtype->getView());
        }
        $catalogue->setService($service);

        //Ajout catalogue en BDD
        try {
            $this->catalogueResourceRepository->add($catalogue, true);
        } catch (ORMException $e) {
            echo 'ORMException : '.$e->getMessage();
        }

        if ($this->platformMode == 'ressourcerie') {
            // Set status to pending if referer contains ressourcerie/submission
            if (strpos($request->headers->get('referer'), 'ressourcerie/submission') !== false) {
                $workflow = $this->workflows->get($catalogue, 'ressourcerie_catalog');
                $workflow->apply($catalogue, 'new_submission', ['user' => $this->getUser()->getUserIdentifier()]);
                $this->catalogueResourceRepository->add($catalogue, true);
            }
            // and set status to validated if referer contains regex ressourcerie/*/catalogue/create
            elseif (preg_match('#ressourcerie/.*/catalogue/create#', $request->headers->get('referer')) === 1) {
                $workflow = $this->workflows->get($catalogue, 'ressourcerie_catalog');
                $workflow->apply($catalogue, 'new_catalog', ['user' => $this->getUser()->getUserIdentifier()]);
                $this->catalogueResourceRepository->add($catalogue, true);
            }
        }

        //Déplacer l'image dans le dossier uploads
        if($image !== null) {
            $filename = $catalogTool->uploadImage($image, 'catalog_'.$catalogue->getId());
            $catalogue->setImage($filename);
            try {
                $this->catalogueResourceRepository->add($catalogue, true);
            } catch (OptimisticLockException|ORMException $e) {
                echo 'ORMException : '.$e->getMessage();
            }
        }

        //ajout des ressources dans le catalogue
        if(isset($parameters['resources'])) {
            $resources = json_decode($parameters['resources']);
        } else {
            $resources = [];
            for($i = 0; $i<=$parameters['number']; $i++) {
                $resources[] = array('title' => '', 'inventoryNumber' => null);
            }
        }

        $resourceController->addResource($resources, $catalogue, $service);

        return new Response($catalogue->getId() ?? true);
    }

    #[Route(path: '/api/catalogue', name: 'get_all_catalogue', methods: ['GET'])]
    public function getAllCatalogueForUser(Registry $workflows): JsonResponse
    {
        $userRights = $this->aclRepository->findBy(['user' => $this->getUser()]);
        $catalogueDeServices = [];
        foreach($userRights as $userRight) {
            $service = ['title' => $userRight->getService()->getTitle(), 'id' => $userRight->getService()->getId(), 'type' => $userRight->getService()->getType(),];
            $right = $userRight->getType();
            $catalogues = [];
            $nbWaiting = 0;
            foreach ($userRight->getService()->getCatalogueResources() as $catalogue) {
                $bookings = $catalogue->getBookings()->getValues();
                foreach ($bookings as $booking) {
                    $workflow = $workflows->get($booking);
                    if($workflow->can($booking, 'accepted_moderation')) {
                        $nbWaiting ++;
                    }
                }
                $catalogues[] = ['id' => $catalogue->getId(), 'title' => $catalogue->getTitle(), 'type' => $catalogue->getType()];
            }
            $catalogueDeServices[] = ['service' => $service, 'acl' => $right, 'catalogues' => $catalogues, 'waiting' => $nbWaiting];
        }
        return new JsonResponse($catalogueDeServices);
    }

    #[Route(path: '/api/catalogue/type/{type}', name: 'get_all_catalogue_type', methods: ['GET'])]
    public function getAllCatalogueByType($type): JsonResponse
    {
        $catalogueList = $this->catalogueResourceRepository->findBy(['type' => $type]);
        $catalogues = [];
        foreach ($catalogueList as $catalogue) {
            $catalogues[] = ['id' => $catalogue->getId(), 'title' => $catalogue->getTitle(), 'type' => ['id' => $catalogue->getType()->getId(), 'label' => $catalogue->getType()->getTitle()], 'image' => $catalogue->getImage(), 'service' => $catalogue->getService()->getTitle(), 'nb' => sizeof($catalogue->getResource()->getValues())];
        }
        return new JsonResponse($catalogues);
    }

    #[Route(path: '/api/catalogue/subtype/{type}', name: 'get_all_catalogue_subtype', methods: ['GET'])]
    public function getAllCatalogueBySubType($type): JsonResponse
    {
        $catalogueList = $this->catalogueResourceRepository->findBy(['subType' => $type]);
        $catalogues = [];
        foreach ($catalogueList as $catalogue) {
            $catalogues[] = ['id' => $catalogue->getId(), 'title' => $catalogue->getTitle(), 'type' => ['id' => $catalogue->getType()->getId(), 'label' => $catalogue->getType()->getTitle()], 'subType' => $catalogue->getSubType()->getTitle(),'image' => $catalogue->getImage(), 'service' => $catalogue->getService()->getTitle(), 'nb' => sizeof($catalogue->getResource()->getValues())];
        }
        return new JsonResponse($catalogues);
    }

    #[Route(path: '/api/catalogue/{id}/resources', name: 'get_catalogue_resources', methods: ['GET'])]
    public function getCatalogueResources($id): JsonResponse
    {
        $catalogue = $this->catalogueResourceRepository->find($id);
        $service = $catalogue->getService();
        $serviceArray = ['id' => $service->getId(), 'title' => $service->getTitle()];
        $ressourceArray = [];
        foreach ($catalogue->getResource()->getValues() as $ressource) {
            $ressourceArray[] = ['id' => $ressource->getId(), 'title' => $ressource->getTitle(), 'inventoryNumber' => $ressource->getInventoryNumber()];
        }
        return new JsonResponse($ressourceArray);
    }

    #[Route(path: '/api/catalogue/{id}', name: 'delete_catalogue', methods: ['DELETE'])]
    #[IsGranted('editCatalog', 'id', statusCode: Response::HTTP_FORBIDDEN)]
    public function deleteCatalogue($id, ResourceController $resourceController): Response
    {

        //delete every resource link on catalogue
        try {
            $resourceController->deleteCatalogueResources($id);
        } catch (OptimisticLockException|ORMException $e) {
            return new Response($e, 400);
        }

        //get every booking on this catalog
        $bookings = $this->bookingRepository->findBy(['catalogueResource'=>$id]);

        //Remove every Logs of every Bookings
        foreach ($bookings as $booking) {
            $workflowLogs = $this->workflowLogRepository->findBy(['booking' => $booking->getId()]);
            foreach ($workflowLogs as $workflowLog) {
                $this->workflowLogRepository->remove($workflowLog);
            }
            $this->bookingRepository->remove($booking);
        }



        $catalogue = $this->catalogueResourceRepository->find($id);
        if($catalogue->getProvisions()) {
            foreach ($catalogue->getProvisions() as $provision) {
                try {
                    $this->provisionRepository->remove($provision);
                } catch (OptimisticLockException|ORMException $e) {
                    return new Response($e, 400);
                }
            }
        }

        if($catalogue->getImage() !== null) {
            $fileSystem = new Filesystem();
            $fileSystem->remove($this->getParameter('kernel.project_dir').'/public/uploads/'.$catalogue->getImage());
        }
        try {
            $this->catalogueResourceRepository->remove($catalogue, true);
        } catch (OptimisticLockException|ORMException $e) {
            return new Response($e, 400);
        }
        return new Response(true);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/api/catalogue/{id}', name: 'edit_catalogue', methods: ['POST'])]
    #[IsGranted('editCatalog', 'id', statusCode: Response::HTTP_FORBIDDEN)]
    public function editCatalogue($id, Request $request, CatalogTool $catalogTool): Response
    {
        $parameters = $request->request->all();
        $image = $request->files->get('image');
        $service = $this->serviceRepository->find($parameters['service']);
        $catalogue = $this->catalogueResourceRepository->find($id);
        $catalogue->setTitle($parameters['title']);
        $catalogue->setDescription($parameters['description']);
        $catalogue->setType($this->categoryRepository->find($parameters['type']));
        if (isset($parameters['subType'])) {
            $catalogue->setSubType($this->categoryRepository->find($parameters['subType']));
        }
        if (isset($parameters['localization'])) {
            $localization = $this->localizationRepository->find($parameters['localization']);
            if($localization) {
                $catalogue->setLocalization($localization);
            }
        }
        $catalogue->setService($service);
        $catalogue->setActuator($this->actuatorRepository->find($parameters['actuator']));
        //Ajout catalogue en BDD
        try {
            $this->catalogueResourceRepository->add($catalogue, true);
        } catch (ORMException $e) {
            echo 'ORMException : ' . $e->getMessage();
        }

        //Déplacer l'image dans le dossier uploads
        if ($image !== null) {
            $filename = $catalogTool->uploadImage($image, 'catalog_'.$catalogue->getId());
            $catalogue->setImage($filename);
            try {
                $this->catalogueResourceRepository->add($catalogue, true);
            } catch (OptimisticLockException|ORMException $e) {
                echo 'ORMException : ' . $e->getMessage();
            }
        }
        return new Response(true);
    }

    #[Route(path: '/api/catalogue/{id}/provision', name: 'add_provision_catalogue', methods: ['POST'])]
    #[IsGranted('editCatalog', 'id', statusCode: Response::HTTP_FORBIDDEN)]
    public function addProvisionCatalogue($id, Request $request, ProvisionRepository $provisionRepository, WorkflowRepository $workflowRepository): Response
    {
        $parameters = $request->request->all();
        $responseProvision = json_decode($parameters['provision']);
        $dateStart = date_create_from_format('d/m/Y', $responseProvision->dateStart);
        $dateEnd = date_create_from_format('d/m/Y', $responseProvision->dateEnd);
        $timeStart = date_create_from_format('H:i', $responseProvision->minBookingTime);
        $timeEnd = date_create_from_format('H:i', $responseProvision->maxBookingTime);
        $interval = date_create_from_format('H:i', $responseProvision->BookingInterval);
        $intervalInMinutes = (int)$interval->format('H')*60+(int)$interval->format('i');
        $maxBookingByDay = $responseProvision->maxBookingByDay;
        $maxBookingByWeek = $responseProvision->maxBookingByWeek;
        $multipleBookingByDay = $responseProvision->allowMultipleDay;
        $targetWorkflow = $workflowRepository->find($responseProvision->attachedWorkflow->id);
        $catalogue = $this->catalogueResourceRepository->find($id);
        if($catalogue) {
            if($responseProvision->id === null) {
                $provision = new Provision();
            } else {
                $provision = $provisionRepository->find($responseProvision->id);
            }
            if($provision) {
                $provision->setCatalogueResource($catalogue);
                $provision->setDateStart($dateStart);
                $provision->setDateEnd($dateEnd);
                $provision->setMinBookingTime($timeStart);
                $provision->setMaxBookingTime($timeEnd);
                $provision->setMaxBookingDuration($responseProvision->maxBookingDuration);
                $provision->setBookingInterval($intervalInMinutes);
                $provision->setDays($responseProvision->days);
                $provision->setWorkflow($targetWorkflow);
                $provision->setMaxBookingByDay($maxBookingByDay);
                $provision->setMaxBookingByWeek($maxBookingByWeek);
                $provision->setAllowMultipleDay($multipleBookingByDay);

                $provision->removeAllGroups();
                foreach ($responseProvision->allGroups as $visibility) {
                    $group = $this->groupRepository->find($visibility->id);
                    $provision->addGroup($group);
                }

                try {
                    $this->provisionRepository->add($provision, true);
                    return new Response(null,200);
                } catch (OptimisticLockException|ORMException $e) {
                    return new Response("Erreur de la base de données",400);
                }
            }

        }
        return new Response("Le catalogue n'existe pas",400);
    }

    #[Route(path: '/api/catalogue/{id}/provision/{idProvision}', name: 'del_provision_catalogue', methods: ['DELETE'])]
    #[IsGranted('editCatalog', 'id', statusCode: Response::HTTP_FORBIDDEN)]
    public function removeProvisionCatalogue($id, $idProvision): Response
    {
        $provision = $this->provisionRepository->find($idProvision);

        if($provision) {
            $catalogue = $this->catalogueResourceRepository->find($id);
            if($catalogue) {
                try {
                    $this->provisionRepository->remove($provision, true);
                } catch (OptimisticLockException|ORMException $e) {
                    return new Response($e, '400');
                }

                return new Response("", '200');
            }

        }

        return new Response("Le planning n'existe pas", '400');
    }

    #[Route(path: '/api/catalogue/{id}/provisions', name: 'get_provision_catalogue', methods: ['GET'])]
    public function getProvisionsFromCatalogue($id) {
        $catalogue = $this->catalogueResourceRepository->find($id);
        if($catalogue) {
            $provisions = $catalogue->getProvisions()->getValues();
            $provisionTab = [];
            if($provisions) {
                foreach ($provisions as $provision) {
                    $provisionTab[] =
                        [
                            'id' => $provision->getId(),
                            'dateStart' => $provision->getDateStart(),
                            'dateEnd' => $provision->getDateEnd(),
                            'minBookingTime' => $provision->getMinBookingTime(),
                            'maxBookingTime' => $provision->getMaxBookingTime(),
                            'bookingInterval' => $provision->getBookingInterval(),
                            'visibility' => $provision->getVisibility(),
                            'workflow' => $provision->getWorkflow(),
                            'days' => $provision ->getDays()
                        ];
                }
            }
            return new JsonResponse($provisionTab);
        }

        return new Response("le catalogue n'existe pas", 400);
    }
}
