<?php

namespace App\Controller;

use App\Repository\CatalogueResourceRepository;
use App\Repository\ProvisionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\DateTime;


class ProvisionController extends AbstractController
{
    public function __construct(private ProvisionRepository $provisionRepository){}

    #[Route(path: '/api/provision/subtype/{type}', name: 'get_all_provusion_subtype', methods: ['GET'])]
    public function getAllCatalogueBySubType($type, Request $request): JsonResponse
    {
        $start = new \DateTime();
        $end = new \DateTime();
        $end->setTimestamp($request->get('dateEnd'));
        $start->setTimestamp($request->get('dateStart'));
        //interval a utiliser plus tard (temps de réservation)
        $interval = $end->diff($start)->h;
        $dayStart = strtolower($start->format('l'));
        $dayEnd = strtolower($end->format('l'));
        $provisions = $this->provisionRepository->findCatalogueFromDate($type, $start, $end, $dayStart, $dayEnd);
        $arrayOfCatalogue = [];
        foreach ($provisions as $provision) {
            $bookings = $provision->getCatalogueResource()->getBookings()->getValues();
            $catalogue = $provision->getCatalogueResource();
            $catalogueSize = sizeof($catalogue->getResource()->getValues());
            $countBooking = 0;
            if ($bookings) {
                foreach ($bookings as $booking) {
                    //Bien définir l'interval entre chaque réservation
                    $bookingEnd = $booking->getDateEnd()->add(new \DateInterval("PT".$provision->getBookingInterval()->format('h').'H'));
                    if($booking->getStatus() !== "close") {
                        if(($booking->getDateStart() <= $start) && ($start < $bookingEnd)) {
                            $countBooking++;
                        } else if (($booking->getDateStart() <= $end) && ($end < $bookingEnd)) {
                            $countBooking++;
                        }
                    }
                }
                if($countBooking<$catalogueSize) {
                    $arrayOfCatalogue[] = ['id' => $catalogue->getId(), 'title' => $catalogue->getTitle(), 'description' => $catalogue->getDescription(), 'type' => ['id' => $catalogue->getType()->getId(), 'label' => $catalogue->getType()->getTitle(), 'subtype' => ['label' => $catalogue->getSubType()->getTitle(), 'id' => $catalogue->getSubType()->getId()]], 'image' => $catalogue->getImage(), 'service' => $catalogue->getService()->getTitle(), 'nb' => sizeof($catalogue->getResource()->getValues()), 'start' => $start, 'end' => $end, 'number' => $catalogueSize-$countBooking];
                }
            } else {
                $arrayOfCatalogue[] = ['id' => $catalogue->getId(), 'title' => $catalogue->getTitle(), 'description' => $catalogue->getDescription(), 'type' => ['id' => $catalogue->getType()->getId(), 'label' => $catalogue->getType()->getTitle(), 'subtype' => ['label' => $catalogue->getSubType()->getTitle(), 'id' => $catalogue->getSubType()->getId()]], 'image' => $catalogue->getImage(), 'service' => $catalogue->getService()->getTitle(), 'nb' => sizeof($catalogue->getResource()->getValues()), 'start' => $start, 'end' => $end, 'number' => $catalogueSize];
            }
        }
        return new JsonResponse($arrayOfCatalogue);
    }
}
