<?php

namespace App\Command;

use App\Entity\WorkflowLog;
use App\Repository\BookingRepository;
use App\Repository\WorkflowLogRepository;
use App\Service\RemoteService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:schedule-castel-bookings',
    description: 'Update Castel accreditations for Bookings',
)]
class ScheduleCastelBookingsCommand extends Command
{
    public function __construct(private BookingRepository $bookingRepository,
                                private WorkflowLogRepository $workflowLogRepository,
                               private SerializerInterface $serializerInterface,
                                private RemoteService $remoteService
    )
    {
        parent::__construct();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateStart = new \DateTime();
        $dateEnd = new \DateTime();
        $bookings = $this->bookingRepository->getBookings($dateStart->setTime(0, 1), $dateEnd->setTime(23, 59));
        foreach ($bookings as $booking) {
            $booking = $this->bookingRepository->find($booking['id']);
            $actuator = $booking->getCatalogueResource()->getActuator();
            $encodedBbooking = json_decode($this->serializerInterface->serialize($booking, 'json', [
                'groups' => ['booking::export'], // optionnel si tu veux gérer les champs affichés
                'skip_null_values' => true,
                'iri' => false
            ]));
            if($actuator !== null && ($booking->getStatus() === 'accepted' || $booking->getStatus() === 'progress')) {
                $actuator = $this->remoteService->executeActuator(strtolower($actuator->getType()), 'create', $encodedBbooking);
                $workflowLog = new WorkflowLog();
                $workflowLog->setBooking($booking);
                $workflowLog->setDate(new \DateTime(date("Y-m-d H:i:s")));
                $workflowLog->setStatusTarget('checkActuator');
                $workflowLog->setComment($actuator);
                $this->workflowLogRepository->add($workflowLog, true);
            }
        }
        $now = new \DateTime();
        $day = $now->format('d-m-y H:i');
        $output->writeln($day. ' : '. 'SCHEDULE-CASTEL-DAY:SUCCESS');
        return Command::SUCCESS;
    }
}
