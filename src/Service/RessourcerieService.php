<?php

namespace App\Service;

use App\Entity\Booking;
use App\Repository\UserRepository;
use App\Repository\BookingRepository;
use App\Repository\ResourceRepository;
use Symfony\Component\Workflow\Registry;

class RessourcerieService {

    public function __construct(
        private BookingRepository $bookingRepository, 
        private ResourceRepository $resourceRepository,
        private UserRepository $userRepository,
        private Registry $workflowRegistry
    ) {}


    /**
     * Save effect books for Ressourcerie
     */
    public function bookEffects($resourceIds, $targetId, $username) {
        $resources = $this->resourceRepository->findBy(['id' => $resourceIds]);
        $booking = new Booking();
        $booking->setStatus('init');
        $now = new \DateTime();
        $booking->setDateStart($now);
        $booking->setDateEnd($now);
        $booking->setNumber(count($resources));
        foreach ($resources as $resource) {
            $booking->addResource($resource);
        }
        $booking->setCatalogueResource($resources[0]->getCatalogueResource());
        $requester = $this->userRepository->findOneBy(['username' => $username]);
        $booking->addUser($requester);
        // Use title global attribute to save the target for Ressourcerie
        $booking->setTitle($targetId);
        $this->bookingRepository->add($booking);

        // $booking->setStatus('requested');
        $workflow = $this->workflowRegistry->get($booking, 'ressourcerie_effect');
        $workflow->apply($booking, 'start_request');

        $this->bookingRepository->add($booking, true);
    }
}