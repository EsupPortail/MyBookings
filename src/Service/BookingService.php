<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\CatalogueResource;
use App\Entity\Period;
use App\Entity\PeriodBracket;
use App\Entity\Provision;
use App\Entity\Resource;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\CatalogueResourceRepository;
use App\Repository\CustomFieldRepository;
use App\Repository\GroupRepository;
use App\Repository\ResourceRepository;
use App\Repository\UserRepository;
use App\Repository\WorkflowLogRepository;
use App\Tools\UserTools;
use DateTime;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use PHPUnit\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Workflow\Registry;

class BookingService
{
    public function __construct(private BookingRepository $bookingRepository,
                                private Registry $workflows,
                                private RuleService $ruleService,
                                private GroupRepository $groupRepository,
                                private UserRepository $userRepository,
                                private UserServiceInterface $userService,
                                private CatalogueResourceRepository $catalogueResourceRepository,
                                private CustomFieldRepository $customFieldRepository,
                                private ResourceRepository $resourceRepository,
                                private UserTools $userTools,
                                private StatisticService $statisticService,
                                private WorkflowLogRepository $workflowLogRepository
    )
    {
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws \Exception
     */
    public function createNewBooking($book, $userIdentifier, $isBookingNow = false): int|\Exception
    {
        $booking = new Booking();
        $catalogue = $this->catalogueResourceRepository->find($book->catalogue);
        $start =  new DateTime();
        $start = $start->createFromFormat('Y-m-d H:i:s',$book->dateStart);
        $end = new DateTime();
        $end = $end->createFromFormat('Y-m-d H:i:s',$book->dateEnd);
        if(!$catalogue)
            throw new \Exception("Le catalogue n'existe pas");

        $store = new FlockStore('/var/www/html/var/locks');
        $factory = new LockFactory($store);
        $lock = $factory->createLock('catalog_'.$catalogue->getId().'_s_'.$start->getTimestamp().'_e_'.$end->getTimestamp());
        $maxRetries = 5;
        $retryDelay = 2000;
        $retryCount = 0;
        while($retryCount < $maxRetries) {
            if ($lock->acquire(false)) {
                $authorUser = $this->userRepository->findOneBy(['username' => $userIdentifier]);
                $userIsAdmin = in_array('ROLE_ADMINSITE_'.$catalogue->getService()->getId(), $authorUser->getRoles()) || in_array('ROLE_MODERATOR_'.$catalogue->getService()->getId(), $authorUser->getRoles());
                $booking->setDateStart($start);
                $booking->setDateEnd($end);
                $booking->setCatalogueResource($catalogue);
                $booking->setNumber($book->number);
                $booking->setUserComment($book->comment);
                if(!empty($book->title)) {
                    $booking->setTitle($book->title);
                }
                if($book->moderatorBooking === false) {
                    $booking->addUser($authorUser);
                }

                try {
                    $this->checkUserRestrictions($authorUser, $booking, $userIsAdmin);
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage());
                }

                $users = json_decode($book->users);
                $decodedUsers = [];
                if(sizeof($users) > 0) {
                    foreach ($users as $user) {
                        $username = $this->userTools->decode($user->value);
                        $userfound = $this->userRepository->findOneBy(['username' => $username]);
                        if($userfound === null) {
                            $newUser = $this->userService->createUser($username);
                            $decodedUsers[] = $newUser;
                            if($newUser) {
                                $this->userRepository->add($newUser, true);
                                $booking->addUser($newUser);
                            }
                        } else {
                            $decodedUsers[] = $userfound;
                            try {
                                $this->checkUserRestrictions($userfound, $booking, $userIsAdmin, true);
                            } catch (\Exception $e) {
                                throw new \Exception($e->getMessage());
                            }
                            $booking->addUser($userfound);
                        }
                    }
                }

                $booking->setStatus("init");
                try {
                    $this->bookingRepository->add($booking);
                } catch (OptimisticLockException|ORMException $e) {
                    throw new \Exception($e->getMessage());
                }

                if($book->resourceId !== null) {
                    $resource = $this->resourceRepository->find($book->resourceId);
                    $resourceChecked = $this->checkResource($booking, $resource, $booking->getDateStart(), $booking->getDateEnd(), $isBookingNow, $userIsAdmin);

                    if($resourceChecked === $resource) {
                        $store = new FlockStore('/var/www/html/var/locks');
                        $factory = new LockFactory($store);
                        $lock = $factory->createLock('resource_'.$book->resourceId.'_s_'.$booking->getDateStart()->getTimestamp().'_e_'.$booking->getDateEnd()->getTimestamp());
                        if (!$lock->acquire()) {
                            throw new \Exception('En cours de réservation par un autre utilisateur');
                        }
                        $booking->addResource($this->resourceRepository->find($book->resourceId));
                    } else {
                        throw new \Exception("La ressource demandée n'est pas disponible selon les règles indiquées par l'administration du site.");
                    }
                }

                $optionsToCheck = [];
                if(isset($book->optionsSelected)) {
                    foreach ($book->optionsSelected as $option) {
                        $customField = $this->customFieldRepository->find($option);
                        if($customField) {
                            $booking->addOption($customField);
                            if($customField->isIsAttribute()) {
                                $optionsToCheck[] = $customField->getId();
                            }
                        }
                    }
                }
                // Update workflow and resource assignment
                $this->setWorkflowAndResource($booking, $optionsToCheck, $userIdentifier, $book->number, $isBookingNow, $userIsAdmin);
                $this->statisticService->addNewBooking($booking, $authorUser, $decodedUsers, true);
                return $booking->getId();
            }
            usleep($retryDelay * 1000);
            $retryCount++;
        }
        throw new \Exception("Impossible de réserver, veuillez réessayer plus tard");
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws \Exception
     */
    public function setWorkflowAndResource(Booking $booking, $selectedOptions, $username, $number, $isBookingNow=false, $isAdmin= false): void
    {
        try {
            $provision = $this->getRelevantProvision($booking->getCatalogueResource(), $booking->getDateStart(), $booking->getDateEnd(), $username);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $booking->setWorkflow($provision->getWorkflow());

        if(sizeof($booking->getResource()->getValues()) === 0) {
            $multipleBookingOption = $booking->getCatalogueResource()->isIndependentOptions();
            try {
                $tmpBooking = $this->addResourceToBooking($booking, $provision, $selectedOptions,$number, $isBookingNow, $multipleBookingOption, $isAdmin);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
            if(!$this->checkIfResourceAreCurrentlyLocked($tmpBooking))
                $booking = $tmpBooking;
        }
        $workflow = $this->workflows->get($booking, 'booking_instance');
        if($provision->getWorkflow()->isAutoValidation() || $isAdmin) {
            $workflow->apply($booking, 'accepted_auto', ['author' => $username]);
        } else {
            $workflow->apply($booking, 'waiting_moderation', ['author' => $username]);
        }

        $this->bookingRepository->add($booking, true);
    }

    /**
     * @throws \Exception
     */
    private function addResourceToBooking(Booking $booking, $provision, $selectedOptions,$number, $isBookingNow = false, $multipleOptionBooking = false, $isAdmin = false): Booking
    {
        $store = new FlockStore('/var/www/html/var/locks');
        $factory = new LockFactory($store);
        //if resource was not added before
        if(sizeof($booking->getResource()) === 0) {
            try {
                if($multipleOptionBooking) {
                    $resources = $this->getAllAvailableResourceFromProvision($provision, $booking->getDateStart(), $booking->getDateEnd(), $booking, $selectedOptions, $isBookingNow, $isAdmin);
                    foreach ($resources as $resource) {
                        $booking->addResource($resource);
                    }
                } else if($number > 1) {
                    for ($i = 0; $i < $booking->getNumber(); $i++) {
                        $return = $this->getAvailableResourceFromProvision($provision, $booking->getDateStart(), $booking->getDateEnd(), $booking, $selectedOptions, $isBookingNow, $isAdmin);
                        $resourceToGive = [];
                        foreach ($return[0] as $resource) {
                            $lock = $factory->createLock('resource_'.$resource->getId().'_s_'.$booking->getDateStart()->getTimestamp().'_e_'.$booking->getDateEnd()->getTimestamp());
                            if ($lock->acquire() && sizeof($resourceToGive) < $number) {
                                $resourceToGive[] = $resource;
                            }
                        }
                        if(sizeof($resourceToGive) < $number) {
                            throw new \Exception($return[1]);
                        } else {
                            if(sizeof($resourceToGive) === (int)$number) {
                                foreach ($resourceToGive as $resource) {
                                    $booking->addResource($resource);
                                }
                            } else {
                                throw new \Exception("Nombre de ressources disponibles insuffisant");
                            }
                        }
                    }
                } else {
                    for ($i = 0; $i < $booking->getNumber(); $i++) {

                        $return = $this->getAvailableResourceFromProvision($provision, $booking->getDateStart(), $booking->getDateEnd(), $booking, $selectedOptions, $isBookingNow, $isAdmin);
                        $resourceToGive = null;

                        $shuffledResources = $return[0];
                        // Shuffling the resources so they all get used equally
                        shuffle($shuffledResources);

                        foreach ($shuffledResources as $resource) {
                            $lock = $factory->createLock('resource_'.$resource->getId().'_s_'.$booking->getDateStart()->getTimestamp().'_e_'.$booking->getDateEnd()->getTimestamp());
                            if ($lock->acquire()) {
                                $resourceToGive = $resource;
                                break;
                            }
                        }
                        if($resourceToGive === null) {
                            throw new \Exception($return[1]);
                        } else {
                            return $booking->addResource($resourceToGive);
                        }
                    }
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        return $booking;
    }


    /**
     * @param CatalogueResource $catalogueResource
     * @param $start
     * @param $end
     * @return mixed
     * @throws \Exception
     */
    public function getRelevantProvision(CatalogueResource $catalogueResource, $start, $end, $username, $invited = false): Provision|bool
    {
        $provisions = $catalogueResource->getProvisions()->getValues();
        foreach ($provisions as $provision) {
            $groups = $provision->getAllGroups();
            $isUserInGroup = true;
            //Check if the user is in a group that allows booking
            if(!$invited) {
                $isUserInGroup = $this->checkIfUserAllowedToBook($groups, $catalogueResource->getService(), $username);
            }
            if($isUserInGroup) {
                if($provision->getPeriodBracket()) {
                    if ($this->checkBracketInDateRange($provision->getPeriodBracket(), $start, $end)) return $provision;
                } else {
                    //Vérification des heures de début et fin
                    $startHour = $start->format('H:i:s');
                    $endHour = $end->format('H:i:s');
                    $minHour = $provision->getMinBookingTime()->format('H:i:s');
                    $maxHour = $provision->getMaxBookingTime()->format('H:i:s');
                    if($minHour <= $startHour && $endHour <= $maxHour) {
                        //Check if the booking is allowed on the selected day
                        if(in_array(strtolower($start->format('l')), $provision->getDays())) {
                            return $provision;
                        }
                    } else {
                        throw new \Exception("Les horaires de réservation ne sont pas autorisés");
                    }
                }
            }
        }
        throw new \Exception('Vous n\'êtes pas autorisé à réserver.');
    }

    /**
     * @throws \Exception
     */
    public function getProvisionByDate(CatalogueResource $catalogueResource, $start, $end, $username): Provision|null
    {
        $provisions = $catalogueResource->getProvisions()->getValues();
        foreach ($provisions as $provision) {
            $groups = $provision->getAllGroups();
            $isUserInGroup = $this->checkIfUserAllowedToBook($groups, $catalogueResource->getService(), $username);
            if($isUserInGroup) {
                //Check start and end date
                if ($provision->getPeriodBracket()) {
                    if ($this->checkBracketInDateRange($provision->getPeriodBracket(), $start, $end)) return $provision;
                } else {
                    //TO DELETE AFTER MIGRATION
                    if ($provision->getDateStart() <= $start && $provision->getDateEnd() >= $end) {

                        $groups = $provision->getAllGroups();

                        $isUserInGroup = $this->checkIfUserAllowedToBook($groups, $catalogueResource->getService(), $username);
                        if ($isUserInGroup) {
                            //Vérification des heures de début et fin
                            if (in_array(strtolower($start->format('l')), $provision->getDays())) {
                                return $provision;
                            }
                        }
                    }
                }
            }

        }
        return null;
    }

    private function checkBracketInDateRange(PeriodBracket $bracket, $start, $end): Bool
    {
        $periods = $bracket->getPeriods();
        foreach ($periods as $period) {
            // Vérifier si la période couvre les dates demandées
            if ($this->isDateRangeInPeriod($period, $start, $end)) {
                return true;
            }
        }
        return false;
    }

    private function checkIfUserAllowedToBook($groups, $service, $username): bool
    {

        // Check if the user has a specific role for the service
        $user = $this->userRepository->findOneBy(['username' => $username]);
        foreach($user->getAcls() as $acl) {
            if($acl->getService()->getId() === $service->getId()) {
                if($acl->getType() === 'ROLE_ADMINSITE') {
                    return true;
                }
            }
        }

        // Check if the user is in a group that has access to the service
        foreach ($groups as $group) {
            $groupObject = $this->groupRepository->find($group['id']);
            $users = json_decode($groupObject->getUsers());
            if(in_array($username, $users)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @throws \Exception
     */
    public function checkSpecificResourceAvailable($resource, $dateStart, $dateEnd, $booking, $selectedOptions, $isBookingNow = false, $isAdmin = false): array
    {
        $checkResource = $this->checkResource($booking, $resource, $dateStart, $dateEnd, $isBookingNow, $isAdmin, $selectedOptions);
        $resources = count($booking->getCatalogueResource()->getResource()->getValues());
        if($checkResource === $resource) {
            return [$resource];
        } else {
            throw new \Exception(json_encode($this->formalizeErrorReturned([$checkResource], $resources)));
        }
    }

    /**
     * @throws \Exception
     */
    public function getAvailableResourceFromProvision(Provision $provision, $dateStart, $dateEnd, $booking, $selectedOptions, $isBookingNow = false, $isAdmin = false): array
    {
        $resources = $provision->getCatalogueResource()->getResource()->getValues();
        $resourcesArray = [];
        $errorResponse = [];
        foreach ($resources as $resource) {
            $checkResource = $this->checkResource($booking, $resource, $dateStart, $dateEnd, $isBookingNow, $isAdmin, $selectedOptions);
            if($checkResource === $resource) {
                $resourcesArray[] = $resource;
                $resourcesState[$resource->getTitle()] = 'Ressource en cours de réservation par un autre utilisateur';
            } else {
                $errorResponse[] = $checkResource;
            }

        }

        $errors = json_encode($this->formalizeErrorReturned($errorResponse, count($resources)));

        if(sizeof($resourcesArray) > 0) {
            return [$resourcesArray, $errors];
        }

        throw new \Exception($errors);
    }

    /**
     * @throws \Exception
     */
    public function getAllAvailableResourceFromProvision(Provision $provision, $dateStart, $dateEnd, $booking, $selectedOptions, $isBookingNow = false, $isAdmin = false): array
    {
        $resources = $provision->getCatalogueResource()->getResource()->getValues();
        $errorResponse = [];
        $resourcesArray = [];
        $isEverythingWrong = true;
        foreach ($resources as $resource) {
            $checkResource = $this->checkResource($booking, $resource, $dateStart, $dateEnd, $isBookingNow, $isAdmin, $selectedOptions, true);
            if($checkResource === $resource) {
                $isEverythingWrong = false;
                $resourcesArray[] = $resource;
            } else {
                $errorResponse[] = $checkResource;
            }
        }
        if($isEverythingWrong === false) {
            return $resourcesArray;
        }
        throw new \Exception(json_encode($this->formalizeErrorReturned($errorResponse, count($resources))));
    }

    private function formalizeErrorReturned(array $checkedResource, int $nbResources): array
    {
        $resourcesState = [];
        $resourceBooked = 0;
        foreach ($checkedResource as $error) {
            if(isset($error['rule'])) {
                if(!isset($resourcesState['rules']))
                    $resourcesState['rules'] = [];
                foreach ($error['rule'] as $rule => $resource) {
                    $resourcesState['rules'][$rule][] = $resource;
                    if(str_contains($rule, 'indisponible'))
                        $resourceBooked++;
                }
            }

            if(isset($error['restriction'])) {
                if(!isset($resourcesState['restrictions']))
                    $resourcesState['restrictions'] = [];
                foreach ($error['restriction'] as $restriction => $resource) {
                    $resourcesState['restrictions'][$restriction][] = $resource;
                }
            }
        }

        if($resourceBooked >= $nbResources) {
            $resourcesState['restrictions'] = 'Aucune ressource disponible';
        }
        return $resourcesState;
    }

    public function checkIfResourceIsBookable(Booking $booking, $resource, $isAdmin): Resource|array
    {
        $checkRules = $this->ruleService->checkResourceRules($booking, $resource);
        if($checkRules === $resource || $isAdmin) {
            if(!$booking->getResource()->contains($resource)) {
                return $resource;
            }
        }
        return $checkRules;
    }

    private function checkIfResourceContainsOptions(Resource $resource, $options, $atLeastOneOption = false): Resource|array
    {
        $optionCount = sizeof($options);

        foreach ($resource->getCustomFieldResources() as $customFieldResource) {
            foreach ($options as $option) {
                if($option === $customFieldResource->getCustomField()->getId()) {
                    $optionCount--;
                }
            }
        }

        if($optionCount === 0) {
            return $resource;
        }

        if($atLeastOneOption === true) {
            if($optionCount >= 0 && $optionCount < sizeof($options)) {
                return $resource;
            }
        }

        return ['rule' => ['La ressource ne dispose pas des options demandées' => $resource->getTitle()]];

    }

    public function checkIfResourceIsAvailable($resource, $dateStart, $dateEnd): bool
    {
        $alreadyBooked = $this->bookingRepository->findBookResource($resource->getId(), $dateStart, $dateEnd);
        if(sizeof($alreadyBooked) === 0) {
            return true;
        }
        return false;
    }

    public function checkResource(Booking $booking, $resource, $dateStart, $dateEnd, $isBookingNow = false, $isAdmin = false, $selectedOptions = [], $checkOnlyOneOption = false): Resource|String|array
    {
        if($this->checkIfResourceIsAvailable($resource, $dateStart, $dateEnd)) {
            if(!$isBookingNow) {
                $checkIfResourceIsBookable = $this->checkIfResourceIsBookable($booking, $resource, $isAdmin);
                if($checkIfResourceIsBookable  === $resource) {
                    return $this->checkIfResourceContainsOptions($resource, $selectedOptions, $checkOnlyOneOption);
                } else {
                    return $checkIfResourceIsBookable;
                }
            } else {
                return $resource;
            }
        } else {
            return ['rule' => ["Ressource indisponible" => $resource->getTitle()]];
        }
    }

    private function checkIfResourceAreCurrentlyLocked(Booking $booking): bool
    {
        $store = new FlockStore('/var/www/html/var/locks');
        $factory = new LockFactory($store);
        $locked = false;
        $resources = $booking->getResource()->getValues();
        foreach ($resources as $resource) {
            $lock = $factory->createLock('resource_'.$resource->getId().'_s_'.$booking->getDateStart()->getTimestamp().'_e_'.$booking->getDateEnd()->getTimestamp());
            if(!$lock->acquire()) {
                $locked = true;
            }
        }
        return $locked;
    }

    public function deleteBooking($id, $isAdmin, $identifier): Response
    {
        $booking = $this->bookingRepository->find($id);
        if($booking) {
            try {
                $workflowLogs = $this->workflowLogRepository->findBy(['booking' => $booking]);
                foreach ($workflowLogs as $workflowLog) {
                    $this->workflowLogRepository->remove($workflowLog);
                }
                $workflow = $this->workflows->get($booking, 'booking_instance');
                if($isAdmin) {
                    $workflow->apply($booking, 'deleted', ['user' => $identifier]);
                } else {
                    $workflow->apply($booking, 'deletedUser', ['user' => $identifier]);
                }
                $this->bookingRepository->remove($booking, true);
            } catch (OptimisticLockException|ORMException $e) {
                return new Response($e, 400);
            }
            return new Response(true, 200);
        }
        return new Response("La réservation n'existe pas", 400);
    }

    public function deleteBookingAndLog($id): void
    {
        $booking = $this->bookingRepository->find($id);
        if($booking) {
            $workflowLogs = $this->workflowLogRepository->findBy(['booking' => $booking]);
            foreach ($workflowLogs as $workflowLog) {
                $this->workflowLogRepository->remove($workflowLog);
            }
            $this->bookingRepository->remove($booking, true);
        }
    }

    public function addResource($booking, $resources): Booking
    {
        foreach ($booking->getResource()->getValues() as $resource) {
            $keys = array_column($resources, 'id');
            $index = array_search($resource->getId(), $keys);
            if ($index === false) {
                $booking->removeResource($resource);
            } else {
                unset($resources[$index]);
            }
        }
        foreach ($resources as $resource) {
            $booking->addResource($this->resourceRepository->find($resource->id));
        }

        return $booking;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function editUser(Booking $booking, $users): Booking
    {
        foreach ($booking->getUser()->getValues() as $user) {
            $keys = array_column($users, 'username');
            $index = array_search($user->getUsername(), $keys);
            if ($index === false) {
                $booking->removeUser($user);
            } else {
                unset($users[$index]);
            }
        }

        foreach ($users as $user) {
            $userfound = $this->userRepository->findOneBy(['username' => $user->username]);
            if($userfound === null) {
                $newUser = $this->userService->createUser($user->username);
                if($newUser) {
                    $this->userRepository->add($newUser, true);
                    $booking->addUser($newUser);
                }
            } else {
                $booking->addUser($userfound);
            }
        }
        return $booking;
    }

    /**
     * @throws \Exception
     */
    public function checkUserRestrictions(User $user, Booking $booking, $userIsAdmin, $invited = false): bool
    {
        if($userIsAdmin) {
            return true;
        }

        $provision = $this->getRelevantProvision($booking->getCatalogueResource(), $booking->getDateStart(), $booking->getDateEnd(), $user->getUsername(), $invited);
        $maxBookingPerWeek = $this->checkMaxBookingPerWeek($user, $booking, $provision);
        $maxBookingPerDay = $this->checkMaxBookingPerDay($user, $booking, $provision);
        if(!$maxBookingPerWeek) {
            throw new \Exception($user->getDisplayUserName()." a atteint le nombre maximum de réservations par semaine");
        }
        if(!$maxBookingPerDay) {
            throw new \Exception($user->getDisplayUserName()." a atteint le nombre maximum de réservations par jour");
        }

        return $maxBookingPerWeek && $maxBookingPerDay;
    }

    private function checkMaxBookingPerWeek(User $user, Booking $booking, Provision $provision): bool
    {
        $bookingDate = $booking->getDateStart();
        $dateStart = clone $bookingDate;
        $dateStart->modify('this week monday');
        $dateEnd = clone $bookingDate;
        $dateEnd = $dateEnd->modify('this week sunday');
        $bookings = $this->bookingRepository->findByUser($user, $dateStart, $dateEnd, $booking->getCatalogueResource());
        $bookings[] = $booking;
        $total = (int) ceil($this->countBookingsInMinutes($bookings)/$provision->getMaxBookingDuration());
        if($provision->getMaxBookingByWeek() === 0) {
            return true;
        }
        return $total <= $provision->getMaxBookingByWeek();
    }

    private function checkMaxBookingPerDay(User $user, Booking $booking, Provision $provision): bool
    {
        $bookingDate = $booking->getDateStart();
        $dateStart = clone $bookingDate;
        $dateStart->setTime(0,1);
        $dateEnd = clone $bookingDate;
        $dateEnd = $dateEnd->setTime(23,59);
        $bookings = $this->bookingRepository->findByUser($user, $dateStart, $dateEnd, $booking->getCatalogueResource());
        $bookings[] = $booking;
        $total = (int) ceil($this->countBookingsInMinutes($bookings)/$provision->getMaxBookingDuration());

        if($provision->getMaxBookingByDay() === 0) {
            return true;
        }
        return $total <= $provision->getMaxBookingByDay();
    }

    private function countBookingsInMinutes($bookings): float|int
    {
        $total = 0;
        foreach ($bookings as $booking) {
            $total += ($booking->getDateEnd()->getTimestamp() - $booking->getDateStart()->getTimestamp()) / 60;
        }
        return $total;
    }

    /**
     * Vérifie si une plage de dates est comprise dans une période
     *
     * @param Period $period La période à vérifier
     * @param \DateTime $start Date de début de la réservation
     * @param \DateTime $end Date de fin de la réservation
     * @return bool
     */
    private function isDateRangeInPeriod(Period $period, \DateTime $start, \DateTime $end): bool
    {
        // Vérifier les dates de début et fin si elles sont définies
        if ($period->getDateStart() && $period->getDateEnd()) {
            if ($period->getDateStart() >= $start || $period->getDateEnd() <= $end) {
                return true;
            }
        }

        // Vérifier les heures de début et fin si elles sont définies
        if ($period->getTimeStart() && $period->getTimeEnd()) {
            $startTime = $start->format('H:i:s');
            $endTime = $end->format('H:i:s');
            $periodStartTime = $period->getTimeStart()->format('H:i:s');
            $periodEndTime = $period->getTimeEnd()->format('H:i:s');

            if ($periodStartTime > $startTime || $periodEndTime < $endTime) {
                return true;
            }
        }

        // Vérifier les jours de la semaine si ils sont définis
        if ($period->getDay() && !empty($period->getDay())) {
            // Obtenir le numéro du jour de la semaine (1=lundi, 7=dimanche format ISO)
            $dayOfWeekNumber = (int) $start->format('N');

            if (!in_array($dayOfWeekNumber, $period->getDay())) {
                return false;
            }
        }

        return true;
    }


    /**
     * @throws \DateMalformedPeriodStringException
     */
    public function getBookingsPeriod($dateStart, $dateEnd, $interval): \DatePeriod
    {
        $dateNow = new \DateTime();
        $period = new \DatePeriod($dateStart, $interval, $dateEnd); // Crée les créneaux jusqu'à dateEnd (exclus)

        if($dateStart->format('d-m-YYYY') === $dateNow->format('d-m-YYYY')) {
            foreach ($period as $dateTime) {
                $dateTimeInterval = clone $dateNow;
                $dateTimeInterval->setTime((int)$dateNow->format('H'), 0, 0);
                while($dateTimeInterval < $dateNow) {
                    $dateTimeInterval->add($interval);
                }
                $dateEndInterval = clone $dateNow;
                $dateEndInterval->setTime((int)$dateEnd->format('H'), (int)$dateEnd->format('i'), 0);
                if($dateTimeInterval > $dateNow) {
                    $dateTimeInterval->sub($interval);
                    $period = new \DatePeriod($dateTimeInterval, $interval, $dateEndInterval); // Crée les créneaux jusqu'à dateEnd (exclus)
                    break;
                }
            }
        }
        return $period;
    }

    /**
     * Génère un DatePeriod basé sur les périodes définies dans un PeriodBracket
     *
     * @param PeriodBracket $bracket Le bracket contenant les périodes
     * @param \DateInterval $interval Intervalle entre chaque créneaux
     * @return array Tableau des créneaux disponibles
     * @throws \Exception
     */
    public function getPeriodFromBracket(PeriodBracket $bracket, $dateStart, $dateEnd, \DateInterval $interval, $intervalBetween): array
    {
        $periods = $bracket->getPeriods();
        $availableSlots = [];
        $dateNow = new \DateTime();

        // Séparer les périodes avec dates et sans dates
        $datedPeriods = [];
        $undatedPeriods = [];
        foreach ($periods as $period) {
            if ($period->getDateStart() && $period->getDateEnd()) {
                $datedPeriods[] = $period;
            } else {
                $undatedPeriods[] = $period;
            }
        }

        // Générer les créneaux pour les périodes datées
        $datedSlots = [];
        $closedPeriods = [];
        foreach ($datedPeriods as $period) {
            if ($period->getType() === 'open') {
                $periodSlots = $this->generateSlotsForPeriod($period, $dateStart, $dateEnd, $interval, $dateNow, $intervalBetween);
                $datedSlots = array_merge($datedSlots, $periodSlots);
            } elseif ($period->getType() === 'close') {
                // Stocker les périodes fermées avec leurs intervalles pour comparaison ultérieure
                $closedPeriods[] = $period;
            }
        }
        // Déterminer les jours couverts par les périodes datées
        $coveredDays = [];
        foreach ($datedPeriods as $period) {
            $start = clone $period->getDateStart();
            $end = clone $period->getDateEnd();
            while ($start <= $end) {
                $coveredDays[$start->format('Y-m-d')] = true;
                $start->modify('+1 day');
            }
        }

        // Générer les créneaux pour les périodes sans dates, uniquement pour les jours non couverts
        $undatedSlots = [];
        $currentDate = clone $dateStart;
        while ($currentDate <= $dateEnd) {
            if (!isset($coveredDays[$currentDate->format('Y-m-d')])) {
                foreach ($undatedPeriods as $period) {
                    if ($period->getType() === 'open') {
                        $periodSlots = $this->generateSlotsForPeriod($period, $currentDate, $currentDate, $interval, $dateNow, $intervalBetween);
                        $undatedSlots = array_merge($undatedSlots, $periodSlots);
                    } elseif ($period->getType() === 'close') {
                        // Stocker les périodes fermées pour comparaison ultérieure
                        $closedPeriods[] = $period;
                    }
                }
            }
            $currentDate->modify('+1 day');
        }

        // Fusionner les créneaux datés et non datés
        $availableSlots = array_merge($datedSlots, $undatedSlots);

        // Suppression des créneaux fermés
        if (!empty($closedPeriods)) {
            //Filtre les créneaux dispo
            $availableSlots = array_filter($availableSlots, function($slot) use ($closedPeriods, $dateStart, $dateEnd) {
                foreach ($closedPeriods as $closedPeriod) {
                    $periodDateStart = $closedPeriod->getDateStart();
                    $periodDateEnd = $closedPeriod->getDateEnd();

                    if ($periodDateStart && $periodDateEnd) {
                        // verification date période
                        $slotDate = $slot->format('Y-m-d');
                        if ($slotDate < $periodDateStart->format('Y-m-d') || $slotDate > $periodDateEnd->format('Y-m-d')) {
                            continue;
                        }
                    } else {
                        // verification date semaine
                        $days = $closedPeriod->getDay();
                        if ($days && !in_array((int)$slot->format('N'), $days)) {
                            continue;
                        }
                    }

                    $timeStart = $closedPeriod->getTimeStart();
                    $timeEnd = $closedPeriod->getTimeEnd();

                    if ($timeStart && $timeEnd) {
                        $slotTime = $slot->format('H:i:s');
                        $closedStartTime = $timeStart->format('H:i:s');
                        $closedEndTime = $timeEnd->format('H:i:s');

                        // Verification si le créneau est dans l'intervalle
                        if ($slotTime >= $closedStartTime && $slotTime < $closedEndTime) {
                            return false;
                        }
                    }
                }
                return true;
            });
        }

        // Supprimer les doublons et trier les créneaux
        $availableSlots = array_values(array_unique($availableSlots, SORT_REGULAR));
        sort($availableSlots);

        return $availableSlots;
    }

    /**
     * Calcule la date de début pour une période
     *
     * @param Period $period La période
     * @param \DateTime $dateNow Date actuelle
     * @return \DateTime
     */
    private function calculatePeriodStart(Period $period, \DateTime $dateNow): \DateTime
    {
        // Si la période a une date de début définie, l'utiliser
        if ($period->getDateStart()) {
            return max($period->getDateStart(), $dateNow);
        }

        // Sinon, utiliser la date actuelle
        return clone $dateNow;
    }

    /**
     * Calcule la date de fin pour une période
     *
     * @param Period $period La période
     * @param \DateTime $dateNow Date actuelle
     * @return \DateTime
     */
    private function calculatePeriodEnd(Period $period, \DateTime $dateNow): \DateTime
    {
        // Si la période a une date de fin définie, l'utiliser
        if ($period->getDateEnd()) {
            return $period->getDateEnd();
        }

        // Sinon, définir une date de fin par défaut (par exemple, 30 jours à partir d'aujourd'hui)
        $defaultEnd = clone $dateNow;
        $defaultEnd->add(new \DateInterval('P1D')); // 30 jours

        return $defaultEnd;
    }

    /**
     * Génère les créneaux disponibles pour une période spécifique
     *
     * @param Period $period La période à traiter
     * @param \DateTime $requestStart Date de début demandée
     * @param \DateTime $requestEnd Date de fin demandée
     * @param \DateInterval $interval Intervalle entre chaque créneaux
     * @param \DateTime $dateNow Date actuelle pour filtrer les créneaux passés
     * @return array
     */
    private function generateSlotsForPeriod(Period $period, \DateTime $requestStart, \DateTime $requestEnd, \DateInterval $interval, \DateTime $dateNow, $intervalBetween = 0): array
    {
        // Vérifier si la période concerne les dates demandées avant de calculer les créneaux
        if (!$this->isPeriodRelevantForDateRange($period, $requestStart, $requestEnd)) {
            return []; // Retourner un tableau vide si la période ne concerne pas les dates demandées
        }

        $slots = [];

        // Déterminer la plage de dates à traiter
        $periodStart = clone $requestStart;
        $periodEnd = clone $requestEnd;

        $currentDate = clone $periodStart;

        $diffDays = max(0, (int) $periodStart->diff($periodEnd)->format('%a') + 1);
        for ($i = 0; $i < $diffDays; $i++) {
            if ($i > 0) {
                $currentDate->modify('+1 day');
            }
            if ($this->isDayAllowedInPeriod($period, $currentDate)) {
                $daySlots = $this->generateDaySlots($period, $currentDate, $interval, $dateNow, $intervalBetween);
                $slots = array_merge($slots, $daySlots);
            }
        }
        return $slots;
    }

    /**
     * Vérifie si une période est pertinente pour une plage de dates donnée
     *
     * @param Period $period La période à vérifier
     * @param \DateTime $requestStart Date de début demandée
     * @param \DateTime $requestEnd Date de fin demandée
     * @return bool
     */
    private function isPeriodRelevantForDateRange(Period $period, \DateTime $requestStart, \DateTime $requestEnd): bool
    {
        // Si la période a des dates spécifiques, vérifier qu'elles chevauchent la plage demandée
        if ($period->getDateStart() && $period->getDateEnd()) {
            // Aucun chevauchement si la période se termine avant le début de la demande
            // ou si la période commence après la fin de la demande
            if ($period->getDateEnd() < $requestStart || $period->getDateStart() > $requestEnd) {
                return false;
            }
        }

        // Si la période est définie par des jours de la semaine, vérifier qu'au moins un jour est dans la plage
        if ($period->getDay() && !empty($period->getDay())) {
            $hasMatchingDay = false;
            $currentDate = clone $requestStart;

            while ($currentDate <= $requestEnd) {
                $dayOfWeekNumber = (int)$currentDate->format('N');
                if (in_array($dayOfWeekNumber, $period->getDay())) {
                    $hasMatchingDay = true;
                    break;
                }
                $currentDate->modify('+1 day');
            }

            if (!$hasMatchingDay) {
                return false;
            }
        }

        // Si la période est définie par des heures, vérifier qu'elles sont dans la plage
        if ($period->getTimeStart() && $period->getTimeEnd()) {
            // Si les heures ne se chevauchent pas, la période n'est pas pertinente
            // Cette vérification est simplifiée car on ne vérifie que les heures, pas les dates complètes
            $periodStartTime = $period->getTimeStart()->format('H:i:s');
            $periodEndTime = $period->getTimeEnd()->format('H:i:s');

            // Pour chaque jour dans la plage, vérifier s'il y a un chevauchement d'heures
            $currentDate = clone $requestStart;
            $hasTimeOverlap = false;

            while ($currentDate <= $requestEnd) {
                if ($this->hasTimeOverlap($currentDate, $periodStartTime, $periodEndTime)) {
                    $hasTimeOverlap = true;
                    break;
                }
                $currentDate->modify('+1 day');
            }

            if (!$hasTimeOverlap) {
                return false;
            }
        }

        return true;
    }

    /**
     * Vérifie s'il y a un chevauchement d'heures pour une date donnée
     *
     * @param \DateTime $date La date à vérifier
     * @param string $periodStartTime Heure de début de la période (format H:i:s)
     * @param string $periodEndTime Heure de fin de la période (format H:i:s)
     * @return bool
     */
    private function hasTimeOverlap(\DateTime $date, string $periodStartTime, string $periodEndTime): bool
    {
        // Cette méthode pourrait être étendue pour une vérification plus précise
        // Pour l'instant, on suppose qu'il y a toujours un chevauchement
        return true;
    }

    /**
     * Vérifie si un jour est autorisé dans une période
     *
     * @param Period $period La période à vérifier
     * @param \DateTime $date La date à vérifier
     * @return bool
     */
    private function isDayAllowedInPeriod(Period $period, \DateTime $date): bool
    {
        // Si aucun jour n'est spécifié, tous les jours sont autorisés
        if (!$period->getDay() || empty($period->getDay())) {
            return true;
        }

        $dayOfWeekNumber = (int) $date->format('N');
        return in_array($dayOfWeekNumber, $period->getDay());
    }

    private function generateDaySlots(Period $period, \DateTime $date, \DateInterval $interval, \DateTime $dateNow, $intervalBetween = 0): array
    {
        $slots = [];

        // Si la période est de type "close", ne générer des slots que pour les jours de la période
        if ($period->getType() === 'close' && $period->getDateStart() && $period->getDateEnd()) {
            $periodDateStart = $period->getDateStart()->format('Y-m-d');
            $periodDateEnd = $period->getDateEnd()->format('Y-m-d');
            $currentDate = $date->format('Y-m-d');

            // Si la date courante n'est pas dans la plage de la période close, ne rien retourner
            if ($currentDate < $periodDateStart || $currentDate > $periodDateEnd) {
                return [];
            }
        }

        // Déterminer les heures de début et fin
        $startTime = $period->getTimeStart() ? $period->getTimeStart()->format('H:i:s') : '00:00:00';
        $endTime = $period->getTimeEnd() ? $period->getTimeEnd()->format('H:i:s') : '23:59:59';

        // Créer les DateTime de début et fin pour ce jour
        $dayStart = clone $date;
        $dayStart->setTime(...explode(':', $startTime));

        $dayEnd = clone $date;
        $dayEnd->setTime(...explode(':', $endTime));
        // Si c'est aujourd'hui, commencer à partir de l'heure actuelle arrondie (sans minutes/secondes)
        if ($date->format('Y-m-d') === $dateNow->format('Y-m-d')) {
            $currentHourRounded = clone $dateNow;
            $currentHourRounded->setTime((int)$dateNow->format('H'), 0, 0);
            while ($currentHourRounded<$dateNow) {
                $currentHourRounded->add($interval);
            }

            $currentHourRounded->sub($interval);

            $dayStart = $currentHourRounded;
        }

        // Générer les créneaux
        $currentSlot = clone $dayStart;
        while ($currentSlot <= $dayEnd) {
            // Vérifier que le créneau + durée ne dépasse pas la fin autorisée
            $slotEnd = clone $currentSlot;
            $slotEnd->add($interval);

            if ($slotEnd <= $dayEnd) {
                $slots[] = clone $currentSlot;
            }

            // Ajout de l'intervalle de séparation entre créneaux
            if ($intervalBetween > 0) {
                $currentSlot->add($interval);
                $currentSlot->add(new \DateInterval('PT' . (int)$intervalBetween . 'M'));
            } else {
                $currentSlot->add($interval);
            }
        }

        return $slots;
    }

    public function getBookingAuthor(Booking $booking)
    {
        $workflowLog = $this->workflowLogRepository->findBookingCreator($booking->getId());
        if($workflowLog) {
            $comment = json_decode($workflowLog->getComment());
            $username = $comment->author;
            return $this->userRepository->findOneBy(['username' => $username]);
        }
        return null;
    }
}
