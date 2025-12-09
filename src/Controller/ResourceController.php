<?php

namespace App\Controller;

use App\Entity\CustomFieldResource;
use App\Entity\Resource;
use App\Repository\CatalogueResourceRepository;
use App\Repository\CustomFieldRepository;
use App\Repository\CustomFieldResourceRepository;
use App\Tools\CatalogTool;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ResourceRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ResourceController extends AbstractController
{
    public function __construct(private ResourceRepository $resourceRepository, private CustomFieldRepository $customFieldRepository, private CustomFieldResourceRepository $customFieldResourceRepository){}


    #[Route(path: '/api/resource/{id}', name: 'delete_resource', methods: ['DELETE'])]
    #[IsGranted('editResource', 'id', statusCode: Response::HTTP_FORBIDDEN)]
    public function deleteResource($id): Response
    {
        $ressource = $this->resourceRepository->find($id);
        try {
            $this->deleteRessource($ressource);
        } catch (OptimisticLockException|ORMException $e) {
            return new Response($e, 500);
        }
        return new Response(true);
    }

    #[Route(path: '/api/resource', name: 'add_resource', methods: ['POST'])]
    #[IsGranted('editCatalog', subject: new Expression('request.request.get("catalogue")'), statusCode: Response::HTTP_FORBIDDEN)]
    public function addResourceApi(Request $request, CatalogueResourceRepository $catalogueResourceRepository): Response
    {
        $parameters = $request->request->all();
        if(!empty($parameters['name']) && !empty($parameters['catalogue'])) {
            $newResource = new Resource();
            $newResource->setTitle($parameters['name']);
            if(!empty($parameters['inventoryNumber'])) {
                $newResource->setInventoryNumber($parameters['inventoryNumber']);
            }
            $catalogue = $catalogueResourceRepository->find($parameters['catalogue']);
            $newResource->setCatalogueResource($catalogue);
            $newResource->setService($catalogue->getService());
            try {
                $this->resourceRepository->add($newResource, true);
            } catch (OptimisticLockException $e) {
                echo 'OptimisticLockException : '.$e->getMessage();
            } catch (ORMException $e) {
                echo 'ORMException : '.$e->getMessage();
            }
            return new Response($newResource->getId());
        }
        return new Response('Wrong parameters', 400);
    }

    /**
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     */
    #[Route(path: '/api/resource/{id}', name: 'edit_resource', methods: ['POST'])]
    #[IsGranted('editResource', 'id', statusCode: Response::HTTP_FORBIDDEN)]
    public function editResourceApi($id, Request $request, CatalogTool $catalogTool): Response
    {
        $parameters = $request->request->all();
        $resource = $this->resourceRepository->find($id);
        if($resource && !empty($parameters['name'])) {
            $resource->setTitle($parameters['name']);
            $resource->setInventoryNumber($parameters['inventoryNumber']);
            $resource->setActuatorProfile($parameters['actuator_profile']);
            $resource->setAdditionalInformations($parameters['informations']);
            $options = explode(',', $parameters['options']);
            $this->unlinkCustomFieldResource($options, $id);
            if(!empty($parameters['options'])) {
                foreach ($options as $option) {
                    $customField = $this->customFieldRepository->find($option);
                    if($customField) {
                        $customFieldResource = $this->customFieldResourceRepository->findOneBy(['Resource' => $id, 'CustomField' => $option]);
                        if($customFieldResource === null) {
                            $customFieldResource = new CustomFieldResource();
                            $customFieldResource->setCustomField($customField);
                            $customFieldResource->setResource($resource);
                        }
                        $this->customFieldResourceRepository->add($customFieldResource, true);
                        $resource->addCustomFieldResource($customFieldResource);
                    }
                }
            }
            if(!empty($parameters['editedCustomFields'])) {
                $editedCustomFields = json_decode($parameters['editedCustomFields']);
                if (is_array($editedCustomFields)) {
                    foreach ($editedCustomFields as $key => $value) {
                        $customField = $this->customFieldRepository->find($key);
                        if($customField) {
                            $customFieldResource = $this->customFieldResourceRepository->findOneBy(['Resource' => $id, 'CustomField' => $customField]);
                            $customFieldResource->setValue($value);
                            $this->customFieldResourceRepository->add($customFieldResource, true);
                        }
                    }
                }
            }

            try {
                $this->resourceRepository->add($resource, true);
            } catch (OptimisticLockException $e) {
                echo 'OptimisticLockException : '.$e->getMessage();
            } catch (ORMException $e) {
                echo 'ORMException : '.$e->getMessage();
            }

            //Déplacer l'image dans le dossier uploads
            $image = $request->files->get('image');
            if ($image !== null) {
                $filename = $catalogTool->uploadImage($image, 'resource_'.$resource->getId());
                $resource->setImage($filename);
                try {
                    $this->resourceRepository->add($resource, true);
                } catch (OptimisticLockException|ORMException $e) {
                    echo 'ORMException : ' . $e->getMessage();
                }
            }
            return new Response($resource->getId());
        }
        return new Response('Wrong parameters', 400);
    }

    /**
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     */
    private function unlinkCustomFieldResource($options, $resource)
    {
        $customFieldsResource = $this->customFieldResourceRepository->findBy(['Resource' => $resource]);
        foreach ($customFieldsResource as $customFieldResource) {
            $shouldDelete = true;
            foreach ($options as $option) {
                $customField = $this->customFieldRepository->find($option);
                if($customField && $customField->getId() === $customFieldResource->getCustomField()->getId()) {
                    $shouldDelete = false;
                }
            }
            if($shouldDelete === true && $customFieldResource->getCustomField()->getType() === 'option') {
                $this->customFieldResourceRepository->remove($customFieldResource, true);
            }
        }
    }

    /**
     * @param $resources
     * @param $catalogue
     * @param $service
     * @return void
     */
    public function addResource($resources, $catalogue, $service): void
    {
        foreach ($resources as $resource) {
            $tmpResource = new Resource();
            $tmpResource->setTitle($resource->title);
            $tmpResource->setInventoryNumber($resource->inventoryNumber);
            $tmpResource->setCatalogueResource($catalogue);
            $tmpResource->setService($service);
            try {
                $this->resourceRepository->add($tmpResource, true);
            } catch (OptimisticLockException $e) {
                echo 'OptimisticLockException : '.$e->getMessage();
            } catch (ORMException $e) {
                echo 'ORMException : '.$e->getMessage();
            }
        }
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function deleteCatalogueResources($catalogueId)
    {
        $ressources = $this->resourceRepository->findBy(['catalogueResource' => $catalogueId]);
        foreach ($ressources as $resource) {
            $this->deleteRessource($resource);
        }
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function deleteRessource($ressource) {
        $this->resourceRepository->remove($ressource, true);
    }
}
