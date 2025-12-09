<?php

namespace App\DataFixtures\Processor;
use App\Entity\Booking;
use App\Entity\Group;
use App\Entity\Resource;
use App\Service\GroupService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Fidry\AliceDataFixtures\ProcessorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ResourceProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $manager, private GroupService $groupService, #[Autowire('%env(DEFAULT_GROUP_PROVIDER)%') ] private string $defaultGroupProvider)
    {
    }

    public function preProcess(string $id, object $object): void
    {
        if ($object instanceof Resource) {
            $catalogue = $object->getCatalogueResource();
            if ($catalogue !== null) {
                $object->setService($catalogue->getService());
            }
            $catalogue->addResource($object);
            $this->manager->persist($object);
            $this->manager->persist($catalogue); // Ensure the catalogue is also persisted
        }

        if($object instanceof Booking) {
            if($object->getCatalogueResource()->getProvisions()->getValues()) {
                $provisions = $object->getCatalogueResource()->getProvisions()->getValues();
                foreach ($provisions as $provision) {
                    if($provision->getCatalogueResource()->getId() === $object->getCatalogueResource()->getId()) {
                        $maxDuration = $provision->getMaxBookingDuration();
                        $dateStart = $object->getDateStart();
                        $dateEnd = (clone $dateStart)->modify('+' . ($maxDuration * rand(1,4)) . ' minutes');
                        $object->setDateEnd($dateEnd);
                        if(!$provision->getWorkflow()->isAutoValidation()) {
                            $object->setStatus('pending');
                        }
                    }
                }
            }


        }
    }

    /**
     * @throws ORMException
     */
    public function postProcess(string $id, object $object): void
    {
        if($object instanceof Booking) {
            $resources = $object->getCatalogueResource()->getResource()->getValues();
            if(count($resources)>0) {
                for ($i = 0; $i < $object->getNumber(); $i++) {
                    if(isset($resources[$i]))
                        $object->addResource($resources[$i]);
                }
                $this->manager->persist($object);
                if($id === 'booking_10000')
                    $this->manager->flush();
            }
        }

        if($object instanceof Group) {
            if($this->defaultGroupProvider === 'db') {
                $object->setUsers($object->getQuery());
                $object->setQuery('');
                $this->manager->persist($object);
                $this->manager->flush();
            }
            $this->groupService->updateGroups($object->getId());
        }
    }
}