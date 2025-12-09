<?php

namespace App\ApiPlatform\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Booking;
use App\Entity\CatalogueResource;
use App\Entity\Resource;
use App\Repository\BookingRepository;
use App\Service\BookingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class BookingsProvider implements ProviderInterface
{

    public function __construct(private EntityManagerInterface $entityManager, private Security $security, private ProviderInterface $collectionProvider, private BookingService $bookingService, private BookingRepository $bookingRepository)
    {
    }

    /**
     * @inheritDoc
     * @throws \DateMalformedPeriodStringException|\Exception
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {

        // Récupérer l'utilisateur connecté
        $user = $this->security->getUser();

        if (!$user && !str_contains($operation->getName(), 'getAnonymousBookings')) {
            throw new \LogicException('User not found or not authenticated.');
        }

        if (str_contains($operation->getName(), 'countBookings')) {
            if(!$user) {
                throw new \LogicException('User not found or not authenticated for myBookings.');
            }
            return $this->countBookings($context, $user->getUserIdentifier()); // Removed $user as it wasn't used
        }

        if (str_contains($operation->getName(), 'myBookings')) {
            if (!$user) { // Ensure user exists for this path
                throw new \LogicException('User not found or not authenticated for myBookings.');
            }
            return $this->getMyBookings($user);
        }

        if (str_contains($operation->getName(), 'getAnonymousBookings')) {
            return $this->getAnonymousBookings($operation, $uriVariables, $context);
        }

        return [];



    }

    /**
     * Compte le nombre de réservations qui chevauchent chaque créneau de 30 minutes
     * dans l'intervalle [dateStart, dateEnd).
     *
     * @param array $context Contexte contenant les filtres 'dateStart', 'dateEnd', 'catalogueResource.id', 'status'.
     * @return array Tableau des créneaux avec le nombre de réservations correspondantes.
     * @throws \Exception
     */
    private function countBookings(array $context, $username): array
    {
        // Valider la présence des filtres nécessaires
        if (!isset($context['filters']['dateStart'], $context['filters']['dateEnd'], $context['filters']['status'])) {
            throw new \InvalidArgumentException('Missing required filters: dateStart, dateEnd, status');
        }

        if(!isset($context['filters']['catalogueResource.id']) && !isset($context['filters']['resource.id']))
            throw new \InvalidArgumentException('Missing required filters');
        if(isset($context['filters']['catalogueResource.id'])) {
            $catalogs = $context['filters']['catalogueResource.id'];
            foreach ($catalogs as $catalog) {
                $catalog = $this->entityManager->find(CatalogueResource::class, $catalog);
                $results[] = ['id' => $catalog->getId(), 'schedule' => $this->countBookingsByCatalogueResource($catalog, $context, $username)];
            }
        }

        if(isset($context['filters']['resource.id'])) {
            $resourceId = $context['filters']['resource.id'];
            $resource = $this->entityManager->find(Resource::class, $resourceId);
            if($resource) {
                $results[] = ['id' => $resource->getId(), 'schedule' => $this->countBookingsByCatalogueResource($resource->getCatalogueResource(), $context, $username, $resourceId)];
            }
        }
        // Le tri est implicitement fait par l'ordre de génération des créneaux via DatePeriod
        return $results;
    }

    private function getMyBookings($user): array
    {
        $type = 'Bookings';

        // Construire la requête pour récupérer les réservations où l'utilisateur est présent
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Booking::class, 'b')
            ->join('b.user', 'u')  // Utiliser l'alias de la relation
            ->join('b.catalogueResource', 'c')
            ->join('c.service', 's')
            ->where('u.id = :userId')
            ->andWhere('s.type = :type')
            ->setParameter('userId', $user->getId())
            ->setParameter( 'type', $type)
            ->orderBy('b.dateStart', 'DESC'); // ou DESC selon vos besoins

        return $queryBuilder->getQuery()->getResult();  // ou $user->getBookings() si vous avez une relation
    }

    /**
     * @throws \Exception
     */
    private function getAnonymousBookings(Operation $operation, array $uriVariables = [], array $context = []): array
    {

        if (!$this->hasValidFilters($context)) {
            return [];
        }

        if ($this->isStatusReturned($context)) {
            return [];
        }

        if($this->isDateIntervalValid($context)) {
            return [];
        }

        return $this->collectionProvider->provide($operation, $uriVariables, $context);
    }

    private function hasValidFilters(array $context): bool
    {
        return isset($context['filters'], $context['filters']['dateStart'], $context['filters']['dateEnd'], $context['filters']['catalogueResource.id'], $context['filters']['status']);
    }

    private function isStatusReturned(array $context): bool
    {
        // Attention: 'status' peut être un tableau si plusieurs statuts sont passés en filtre
        $statusFilter = (array) $context['filters']['status'];
        return in_array('returned', $statusFilter, true);
    }

    /**
     * @throws \Exception
     */
    private function isDateIntervalValid(array $context): bool
    {
        $dateStart = new \DateTime($context['filters']['dateStart']);
        $dateEnd = new \DateTime($context['filters']['dateEnd']);

        $interval = $dateStart->diff($dateEnd);

        // Peut-être que vous voulez limiter la durée max de la requête ?
        // Cette logique semble vouloir retourner [] si l'intervalle est > 1 jour.
        // Vérifiez si c'est bien le comportement attendu.
        return $interval->days > 1;
    }

    /**
     * @throws \DateMalformedPeriodStringException
     * @throws \Exception
     */
    private function countBookingsByCatalogueResource(CatalogueResource $catalogueResource, $context, $username = null, $idResource = null): array
    {
        $filterDateStart = new \DateTime($context['filters']['dateStart']);
        $filterDateEnd = new \DateTime($context['filters']['dateEnd']);
        $today = new \DateTime();
        $today->setTime(0,0,0);
        $slots = [];
        if($filterDateStart<$today) {
            return $slots;
        }
        $provision = $this->bookingService->getProvisionByDate($catalogueResource, $filterDateStart, $filterDateEnd, $username);

        if($provision) {
            $nbResources = sizeof($catalogueResource->getResource()->getValues());
            $intervalBetween = $provision->getBookingInterval();
            $interval = new \DateInterval('PT'.$provision->getMaxBookingDuration().'M');
            if($provision->getPeriodBracket()) {
                $period = $this->bookingService->getPeriodFromBracket($provision->getPeriodBracket(), $filterDateStart, $filterDateEnd, $interval, $intervalBetween);
                $intervalStart = $filterDateStart;
                $intervalEnd = $filterDateEnd;
            } else {
               //TODELETE AFTER MIGRATION

               $intervalStart = $filterDateStart->setTime($provision->getMinBookingTime()->format('H'),$provision->getMinBookingTime()->format('i'));
               $intervalEnd =new \DateTime($context['filters']['dateStart']);
               $intervalEnd->setTime($provision->getMaxBookingTime()->format('H'),$provision->getMaxBookingTime()->format('i'));
               $period = $this->bookingService->getBookingsPeriod($intervalStart, $intervalEnd, $interval);
            }


            // 1. Initialiser tous les créneaux à 0
            foreach ($period as $dateTime) {
                $date = $dateTime->format('Y-m-d');
                $time = $dateTime->format('H:i:s');
                $slots[$date]['bookings'][$time] = 0;
                $slots[$date]['occupancyRate'] = 0;
                $slots[$date]['interval'] = $provision->getMaxBookingDuration();
                $slots[$date]['multipleBookingAllowed'] = $provision->isAllowMultipleDay();
            }
            // 2. Récupérer toutes les réservations qui chevauchent la période globale demandée
            //    Une réservation chevauche [filterDateStart, filterDateEnd) si :
            //    booking.dateStart < filterDateEnd ET booking.dateEnd > filterDateStart
            if($idResource === null) {
                $overlappingBookings =  $this->bookingRepository->findBookingByCatalogResource($catalogueResource->getId(), $context['filters']['status'], $intervalStart, $intervalEnd);
            } else {
                $overlappingBookings =  $this->bookingRepository->findBookingByResource($idResource, $context['filters']['status'], $intervalStart, $intervalEnd);
            }
            // 3. Itérer sur les réservations et incrémenter les compteurs des créneaux correspondants
            foreach ($overlappingBookings as $booking) {
                // Itérer sur chaque date
                foreach ($slots as $slotDate => $slotTime) {
                    // Itérer sur chaque créneau de 30 minutes défini précédemment
                    $occupancyRate = 0;
                    foreach ($slotTime['bookings'] as $time => $count) {
                        $slotStart = new \DateTime($slotDate.' '.$time);;
                        $slotEnd = (clone $slotStart)->add($interval); // Calcule la fin du créneau
                        // Vérifier si la réservation [bookingStart, bookingEnd) chevauche le créneau [slotStart, slotEnd)
                        // Condition de chevauchement: startA < endB AND endA > startB
                        if ($booking['dateStart'] < $slotEnd && $booking['dateEnd'] > $slotStart) {
                            $count++;
                        }
                        $date = $slotStart->format('Y-m-d');
                        $time = $slotStart->format('H:i:s');
                        $slots[$date]['bookings'][$time] = $count;
                        $occupancyRate+= $count;
                    }
                    $slots[$slotDate]['occupancyRate'] = 100 - ((($nbResources * sizeof($slotTime['bookings'])) - $occupancyRate) / ($nbResources * sizeof($slotTime['bookings']))) * 100;
                }
            }
        }

        return $slots;
    }
}