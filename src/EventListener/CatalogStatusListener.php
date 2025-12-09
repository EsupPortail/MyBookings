<?php

namespace App\EventListener;

use App\Entity\CatalogueResource;
use App\Repository\CatalogueResourceRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Workflow\Registry;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: CatalogueResource::class)]
class CatalogStatusListener
{

    public function __construct(
        private Registry $workflows,
        private CatalogueResourceRepository $catalogueResourceRepository
    )
    {
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function preUpdate(CatalogueResource $catalogueResource, PreUpdateEventArgs $args)
    {

        if($args->hasChangedField('status') && $args->getOldValue('status') !== null) {
            $workflow = $this->workflows->get($catalogueResource, 'ressourcerie_catalog');
            $catalogueResource->setStatus($args->getOldValue('status'));
            $transitions = $workflow->getEnabledTransitions($catalogueResource);
            foreach ($transitions as $transition) {
                if(in_array($args->getNewValue('status'), $transition->getTos())) {
                    $workflow->apply($catalogueResource, $transition->getName());
                    break;
                }
            }
        }
    }
}