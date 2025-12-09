<?php

namespace App\Controller;


use App\Entity\Booking;
use App\Entity\CatalogueResource;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\CatalogueResourceRepository;
use App\Repository\ResourceRepository;
use App\Repository\StatisticsRepository;
use App\Repository\UserRepository;
use App\Service\BookingService;
use App\Service\Mail;
use App\Service\StatisticService;
use App\Service\UserService;
use App\Tools\UserTools;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Workflow\Registry;


class BookingController extends AbstractController
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private ResourceRepository $resourceRepository,
        private Registry $workflows,
        private BookingService $bookingService,
        private Mail $mail,
        private UserRepository $userRepository,
        private catalogueResourceRepository $catalogueResourceRepository,
        private userTools $userTools,
        private StatisticsRepository $statisticsRepository,
        private StatisticService $statisticService
    ){}

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws \Exception
     */
    #[Route(path: '/api/booking/new', name: 'post_booking', methods: ['POST'])]
    public function createNewBooking(Request $request): Response
    {
        $refer = explode('/',$request->headers->get('referer'));
        $isBookingNow = strpos($refer[sizeof($refer)-1], 'now') !== false;
        $bookings = json_decode($request->get('book'));
        $bookingsId = [];
        if($this->getUser()) {
            foreach ($bookings as $book) {
                try {
                    $bookingsId[] = $this->bookingService->createNewBooking($book, $this->getUser()->getUserIdentifier(), $isBookingNow);
                } catch (\Exception $e) {
                    return new Response($e->getMessage(), 400);
                }
            }
        } else {
            return new Response('disconnected', 400);
        }


        return new JsonResponse($bookingsId, 200);
    }

    #[Route(path: '/api/booking/{id}/confirm', name: 'confirm_booking', methods: ['POST'])]
    public function confirmBooking($id, Request $request): Response
    {
        $booking = $this->bookingRepository->find($id);
        if($booking) {
            $confirm = json_decode($request->get('confirm'));
            $comment = $request->get('comment');
            $resources = json_decode($request->get('resource'));
            if($confirm === true) {
                $booking->setConfirmComment($comment);
                foreach($resources as $resource) {
                    $booking->addResource($this->resourceRepository->find($resource->id));
                }
                $workflow = $this->workflows->get($booking, 'booking_instance');
                $workflow->apply($booking, 'accepted_moderation');
                try {
                    $this->bookingRepository->add($booking, true);
                } catch (OptimisticLockException|ORMException $e) {
                    return new Response($e->getMessage(), 400);
                }
                return new Response(true, 200);
            }

        }
        return new Response("La réservation n'existe pas", 400);
    }

    #[Route(path: '/api/booking/{id}/refuse', name: 'refuse_booking', methods: ['POST'])]
    public function refuseBooking($id, Request $request): Response
    {
        $booking = $this->bookingRepository->find($id);
        if($booking) {
            $workflow = $this->workflows->get($booking, 'booking_instance');
            $workflow->apply($booking, 'deleted', ['comment' => $request->get('comment')]);
            try {
                $statistic = $this->statisticsRepository->getStatisticByBookingId($id);
                if($statistic) {
                    $this->statisticsRepository->remove($statistic, true);
                }
                $this->bookingRepository->remove($booking, true);
            } catch (OptimisticLockException|ORMException $e) {
                return new Response($e, 400);
            }
            return new Response(true, 200);
        }
        return new Response("La réservation n'existe pas", 400);
    }

    #[Route(path: '/api/booking/{id}', name: 'remove_booking', methods: ['DELETE'])]
    public function removeBooking($id): Response
    {
        $statistic = $this->statisticsRepository->getStatisticByBookingId($id);
        if($statistic) {
            $this->statisticsRepository->remove($statistic, true);
        }
        return $this->bookingService->deleteBooking($id, false, $this->getUser()->getUserIdentifier());
    }

    #[Route(path: '/api/booking/{id}/admin', name: 'remove_booking_admin', methods: ['DELETE'])]
    public function removeAdminBooking($id): Response
    {
        return $this->bookingService->deleteBooking($id, true, $this->getUser()->getUserIdentifier());
    }

    #[Route(path:'/api/booking/{id}/start', name:'start_booking', methods: ["POST"])]
    public function startBooking($id, Request $request): Response
    {
        $booking = $this->bookingRepository->find($id);
        if($booking) {
            $resources = json_decode($request->get('resource'));
            foreach ($booking->getResource()->getValues() as $resource) {
                $keys = array_column($resources, 'id');
                $index = array_search($resource->getId(), $keys);
                if($index === false) {
                    $booking->removeResource($resource);
                } else {
                    unset($resources[$index]);
                }
            }
            foreach ($resources as $resource) {
                $booking->addResource($this->resourceRepository->find($resource->id));
            }
            $workflow = $this->workflows->get($booking, 'booking_instance');
            $workflow->apply($booking, 'start_booking');
            try {
                $this->bookingRepository->add($booking, true);
            } catch (OptimisticLockException|ORMException $e) {
                return new Response($e->getMessage(), 400);
            }
            return new Response(true, 200);
        }
        return new Response("La réservation n'existe pas", 400);
    }

    /**
     * @throws \DateMalformedStringException
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route(path:'/api/booking/{id}', name:'edit_booking', methods: ["POST"])]
    public function editBooking($id, Request $request): Response
    {
        $booking = $this->bookingRepository->find($id);
        $users = null;
        if($booking) {
            if($request->get('resource')) {
                $resources = json_decode($request->get('resource'));
                $booking = $this->bookingService->addResource($booking, $resources);
            }
            if($request->get('users')) {
                $users = json_decode($request->get('users'));
                $booking = $this->bookingService->editUser($booking, $users);
            }
            if($request->get('dateStart')) {
                $dateStart = new \DateTime($request->get('dateStart'));
                $booking->setDateStart($dateStart);
            }
            if($request->get('dateEnd')) {
                $dateEnd = new \DateTime($request->get('dateEnd'));
                $booking->setDateEnd($dateEnd);
            }
            try {
                $this->bookingRepository->add($booking, true);
            } catch (OptimisticLockException|ORMException $e) {
                return new Response($e->getMessage(), 400);
            }
            $statistic = $this->statisticsRepository->getStatisticByBookingId($id);
            $this->statisticService->updateStatisticBooking($booking, $statistic, $users);
            $this->mail->sendEditBooking($booking);

            return new Response(true, 200);
        }
        return new Response("La réservation n'existe pas", 400);
    }

    #[Route(path: '/api/booking/{id}/end', name: 'end_booking', methods: ['POST'])]
    public function endBooking($id, Request $request): Response
    {
        $booking = $this->bookingRepository->find($id);
        $booking->setDateEnd(new \DateTime());
        if($booking) {
            $workflow = $this->workflows->get($booking, 'booking_instance');
            $workflow->apply($booking, 'end_booking');
            try {
                $this->bookingRepository->add($booking, true);
            } catch (OptimisticLockException|ORMException $e) {
                return new Response($e, 400);
            }

            return new Response(true, 200);
        }
        return new Response("La réservation n'existe pas", 400);
    }

    #[Route(path: '/api/booking/me', name: 'get_my_bookings', methods: ['GET'])]
    public function getMyBooking(): JsonResponse
    {
        $arrayOfBookings = [];
        foreach ($this->getUser()->getBookings()->getValues() as $booking) {
            $arrayOfBookings[] = ["id" =>$booking->getId(), "start" => $booking->getDateStart(), "catalogue" => ['type' => $booking->getCatalogueResource()->getType()->getTitle(),'subtype' => $booking->getCatalogueResource()->getSubType()->getTitle()], 'number' => $booking->getNumber(), 'status' => $booking->getStatus()];
        }
        return new JsonResponse($arrayOfBookings);
    }

    #[Route(path: '/api/booking/{id}', name: 'get_booking', methods: ['GET'])]
    public function getBooking($id): Response
    {
        $booking = $this->bookingRepository->find($id);

        if($booking) {
            $bookingList = [];
            if($booking !== 'returned') {
                $users = [];
                foreach ($booking->getUser()->getValues() as $user) {
                    $users[] = ['displayName' => $user->getDisplayUserName(), 'email' => $user->getEmail(),'roles' => $user->getRoles()];
                }
                $resources = [];
                foreach ($booking->getResource()->getValues() as $resource) {
                    $resources[] = ['id' => $resource->getId(), 'title' => $resource->getTitle(), "inventoryNumber" => $resource->getInventoryNumber()];
                }
                $bookingList = ['catalogue' => ['id'=> $booking->getCatalogueResource()->getId(), 'type' => $booking->getCatalogueResource()->getType()->getTitle(), 'subtype' => $booking->getCatalogueResource()->getSubType()->getTitle(), 'title' => $booking->getCatalogueResource()->getTitle(), 'image' => $booking->getCatalogueResource()->getImage()] , 'id' => $booking->getId(), 'users' => $users, 'state' => $booking->getStatus(), 'start' => $booking->getDateStart(), 'end' => $booking->getDateEnd(), 'number' => $booking->getNumber(), 'userComment' => $booking->getUserComment(), 'confirmComment' => $booking->getConfirmComment(), 'resources' => $resources];
            }
            return new Response(json_encode($bookingList));
        }

        return new Response("La réservation n'existe pas", 400);
    }

    #[Route(path: '/api/booking/{id}/history', name: 'get_booking_history', methods: ['GET'])]
    public function getBookingHistory($id): Response
    {
        $booking = $this->bookingRepository->find($id);
        $fullHistory = [];
        foreach ($booking->getWorkflowLogs()->getValues() as $history) {
            if ($history->getStatusTarget() === 'waiting_moderation') {
                $fullHistory[] = ['status' => $history->getStatusTarget(), 'date' => $history->getDate(), 'comment' => $booking->getUserComment()];
            } elseif($history->getStatusTarget() === 'accepted_moderation') {
                $fullHistory[] = ['status' => $history->getStatusTarget(), 'date' => $history->getDate(), 'comment' => $booking->getConfirmComment()];
            } elseif ($history->getStatusTarget() === 'checkActuator') {
                $fullHistory[] = ['status' => $history->getStatusTarget(), 'date' => $history->getDate(), 'comment' => $history->getComment()];
            } elseif ($history->getStatusTarget() === 'init_booking') {
                $initComment = json_decode($history->getComment());
                $author = $this->userRepository->findOneBy(['username' => $initComment->author]);
                $fullHistory[] = ['status' => $history->getStatusTarget(), 'date' => $history->getDate(), 'comment' => 'Autheur : ' . $author?->getDisplayUserName()];
            } else {
                $fullHistory[] = ['status' => $history->getStatusTarget(),'date' => $history->getDate(), 'comment' => ''];
            }
        }
        return new JsonResponse($fullHistory);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/api/booking/new/{username}/available', name: 'check_booking_restrictions', methods: ['GET'])]
    #[Route(path: '/api/booking/new/{username}/check', name: 'check_booking_check', methods: ['GET'])]
    public function checkBookingRestrictions($username, Request $request, UserService $userService): Response
    {
        $response = [];
        $resources = [];
        $number = null;
        if($request->get('number')) {
            $number = $request->get('number');
        }
        $user = $this->userRepository->findOneBy(['username' => $username]);
        if(!$user) {
            $user = $this->userRepository->findOneBy(['username' => $this->userTools->decode($username)]);
            if(!$user) {
                throw new NotFoundHttpException('User not found');
            }
        }
        $catalog = $this->catalogueResourceRepository->findOneBy(['id' => $request->get('catalog')]);
        $resourceId = null;
        if($request->get('resource') !== null) {
            $resourceId = $request->get('resource');
        }
        $booking = new Booking();
        $booking->setCatalogueResource($catalog);
        $booking->setDateStart(new \DateTime($request->get('startDate')));
        $booking->setDateEnd(new \DateTime($request->get('endDate')));
        $booking->addUser($user);
        if($request->get('users') !== null) {
            foreach ($request->get('users') as $userInvit) {
                $usernameInvit = $this->userTools->decode($userInvit);
                if($usernameInvit === null)
                    $usernameInvit = $userInvit;
                $u = $this->userRepository->findOneBy(['username' => $usernameInvit]);
                if($u === null) {
                    $u = $userService->createUser($usernameInvit);
                    $this->userRepository->add($u, true);
                }
                $booking->addUser($u);
                try {
                    $this->bookingService->checkUserRestrictions($u, $booking, $this->isUserAdmin($user, $catalog), true);
                } catch (\Exception $e) {
                    $response["restrictions"][] = $e->getMessage();
                }
            }
        }
        $restrictions = [];
        try {
            $restrictions = $this->bookingService->checkUserRestrictions($user, $booking, $this->isUserAdmin($user, $catalog));
        } catch (\Exception $e) {
            $response["restrictions"][] = $e->getMessage();
        }

        if($request->get('_route') === 'check_booking_check') {
            $provision = $this->bookingService->getRelevantProvision($booking->getCatalogueResource(), $booking->getDateStart(), $booking->getDateEnd(), $user->getUsername());

            if($resourceId === null) {
                try {
                    $resources = $this->bookingService->getAllAvailableResourceFromProvision($provision, $booking->getDateStart(), $booking->getDateEnd(), $booking, [], false, $this->isUserAdmin($user, $catalog));
                } catch (\Exception $e) {
                    $response = array_merge($response, json_decode($e->getMessage(), true));
                }
            } else {
                $resource = $this->resourceRepository->find($resourceId);
                try {
                    $resources = $this->bookingService->checkSpecificResourceAvailable($resource, $booking->getDateStart(), $booking->getDateEnd(), $booking, [], false, $this->isUserAdmin($user, $catalog));
                } catch (\Exception $e) {
                    $response = array_merge($response, json_decode($e->getMessage(), true));
                }
            }
        }

        if($response !== []) {
            return new JsonResponse($response, 403);
        }

        if($number) {
            if(sizeof($resources) < $number)
                return new JsonResponse(["restrictions" => "Ressources disponibles insuffisantes"], 403);
        }

        return new Response($restrictions);
    }

    private function isUserAdmin(User $user, CatalogueResource $catalogueResource): bool
    {
        $service = $catalogueResource->getService();

        if(in_array("ROLE_ADMIN", $user->getRoles())) {
            return true;
        }

        foreach($user->getAcls() as $acl) {
            if($acl->getService()->getId() === $service->getId()) {
                if($acl->getType() === 'ROLE_ADMINSITE' || $acl->getType() === 'ROLE_MODERATOR') {
                    return true;
                }
            }
        }
        return false;
    }
}
