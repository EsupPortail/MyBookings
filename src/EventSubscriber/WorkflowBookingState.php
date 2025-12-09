<?php
namespace App\EventSubscriber;

use App\Entity\WorkflowLog;
use App\Repository\BookingRepository;
use App\Repository\WorkflowLogRepository;
use App\Service\BookingService;
use App\Service\Mail;
use App\Service\RemoteService;
use Doctrine\ORM\Exception\ORMException;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class WorkflowBookingState implements EventSubscriberInterface
{
    public function __construct(
        private WorkflowLogRepository $workflowLogRepository,
        private Mail $mail,
        private BookingRepository $bookingRepository,
        private RemoteService $remoteService,
        private SerializerInterface $serializerInterface,
        private BookingService $bookingService
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.booking_instance.transition.waiting_moderation' => 'waitModeration',
            'workflow.booking_instance.transition.accepted_auto' => 'autoAcceptedBooking',
            'workflow.booking_instance.transition.accepted_auto_silent' => 'autoAcceptedBooking',
            'workflow.booking_instance.transition.accepted_moderation' => 'checkActuator',
            'workflow.booking_instance.transition.start_booking' => 'onUpdate',
            'workflow.booking_instance.transition.end_booking' => 'onUpdate',
            'workflow.booking_instance.transition.refused' => 'onUpdate',
            'workflow.booking_instance.transition.deletedUser' => 'sendRemoveByUserMail',
            'workflow.booking_instance.transition.deleted' => 'sendRemoveMail',
        ];
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function waitModeration(Event $event): void
    {
        $workflowLog = new WorkflowLog();
        $booking = $event->getSubject();
        $workflowLog->setBooking($booking);
        $workflowLog->setDate(new \DateTime(date("Y-m-d H:i:s")));
        $workflowLog->setStatusTarget('init_booking');
        if($event->getContext()['author'] !== null) {
            $comment = json_encode(['author' => $event->getContext()['author']]);
            $workflowLog->setComment($comment);
        }
        $this->workflowLogRepository->add($workflowLog, true);
        $this->onUpdate($event);
    }

    /**
     * @throws \SoapFault
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function autoAcceptedBooking(Event $event): void
    {
        $workflowLog = new WorkflowLog();
        $booking = $event->getSubject();
        $workflowLog->setBooking($booking);
        $workflowLog->setDate(new \DateTime(date("Y-m-d H:i:s")));
        $workflowLog->setStatusTarget('init_booking');
        if($event->getContext()['author'] !== null) {
            $comment = json_encode(['author' => $event->getContext()['author']]);
            $workflowLog->setComment($comment);
        }
        $this->workflowLogRepository->add($workflowLog, true);
        $this->checkActuator($event);

    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     * @throws ORMException
     */
    public function onUpdate(Event $event): void
    {
        $workflowLog = new WorkflowLog();
        $booking = $event->getSubject();
        $workflowLog->setBooking($booking);
        $workflowLog->setDate(new \DateTime(date("Y-m-d H:i:s")));
        $workflowLog->setStatusTarget($event->getTransition()->getName());
        $config = null;
        if($booking->getWorkflow() === null) {
            $booking->setStatus('returned');
            $this->bookingRepository->add($booking, true);
        } else {
            $config = $booking->getWorkflow()->getConfiguration();
        }

        $moderators = [];
        if($config !== null) {
            $this->sendUpdateModeration($config['notifications'], $booking, $event);
            $moderators = $this->getModeratorEmails($config['notifications']);
        }
        $this->mail->sendMail($booking, $event->getTransition()->getName(), $moderators);
        $this->workflowLogRepository->add($workflowLog, true);
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function sendRemoveMail(Event $event): void
    {
        $booking = $event->getSubject();
        $comment = $event->getContext()['comment'];
        $this->mail->sendMailRemoved($booking, $event->getTransition()->getName(), $comment);
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function sendRemoveByUserMail(Event $event): void
    {
        $booking = $event->getSubject();
        $this->mail->sendMailRemovedByUser($booking, $event->getTransition()->getName());
        $workflowLog = new WorkflowLog();
        $workflowLog->setDate(new \DateTime(date("Y-m-d H:i:s")));
        $workflowLog->setStatusTarget($event->getTransition()->getName());
        $workflowLog->setComment('Deleted by : '.$event->getContext()['user']);
        $this->workflowLogRepository->add($workflowLog, true);
    }

    public function onlyLogEvent(Event $event): void
    {
        $workflowLog = new WorkflowLog();
        $workflowLog->setBooking($event->getSubject());
        $workflowLog->setDate(new \DateTime(date("Y-m-d H:i:s")));
        $workflowLog->setStatusTarget($event->getTransition()->getName());
        $this->workflowLogRepository->add($workflowLog, true);
    }

    /**
     * @throws \SoapFault
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function checkActuator(Event $event): void
    {
        $actuator = $event->getSubject()->getCatalogueResource()->getActuator();
        // Appel à l'actuateur (remote service
        if($actuator !== null) {
            $booking = json_decode($this->serializerInterface->serialize($event->getSubject(), 'json', [
                'groups' => ['booking::export'],
                'skip_null_values' => true,
                'iri' => false
            ]));
            try {
                $actuator = $this->remoteService->executeActuator(strtolower($actuator->getType()), 'create', $booking);
            } catch (\Exception $e) {
                $this->bookingService->deleteBookingAndLog($event->getSubject()->getId());
                throw new Exception($e->getMessage());
            }


            $workflowLog = new WorkflowLog();
            $workflowLog->setBooking($event->getSubject());
            $workflowLog->setDate(new \DateTime(date("Y-m-d H:i:s")));
            $workflowLog->setStatusTarget('checkActuator');
            $workflowLog->setComment($actuator);
            $this->workflowLogRepository->add($workflowLog, true);
        }
        $this->onUpdate($event);
    }

    private function sendUpdateModeration($notifications, $booking, $event): void
    {
        foreach ($notifications as $key=> $notification) {
            if(str_contains($event->getTransition()->getName(), $key) && $notification !== null || $key === 'accepted' && $event->getTransition()->getName() === 'waiting_moderation' && $notification !== null) {
                $this->mail->sendMailModeration($booking, $event->getTransition()->getName(), $notification);
            }
        }
    }

    private function getModeratorEmails($notifications): array
    {
        $emails = [];
        foreach ($notifications as $notification) {
            if($notification !== null) {
                $emails = array_merge(explode(';', $notification), $emails);
            }
        }
        return array_map('strtolower', $emails);
    }
}