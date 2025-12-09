<?php

namespace App\Command;

use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Workflow\Registry;
use DateTime;

#[AsCommand(
    name: 'app:auto-update-bookings',
    description: 'auto updating bookings',
)]
class AutoUpdateBookingsCommand extends Command
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private Registry $workflows,
        private EntityManagerInterface $entityManager,
    ){parent::__construct();}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new DateTime();
        $day = $now->format('d-m-y H:i');
        $output->writeln($day. ' : '.$this->fromAcceptedToStart($now));
        $output->writeln($day. ' : '.$this->fromStartedToEnd($now));
        $output->writeln($day. ' : '.$this->fromInitToAccept());
        return Command::SUCCESS;
    }

    private function fromAcceptedToStart($today): string
    {
        $bookings = $this->bookingRepository->findAcceptedToStart($today);
        foreach ($bookings as $booking) {
            if($booking->getWorkflow()->getConfiguration()['autoStart'] === true){
                $workflow = $this->workflows->get($booking, 'booking_instance');
                $workflow->apply($booking, 'start_booking');
            }
            try {
                $this->bookingRepository->add($booking, true);
            } catch (OptimisticLockException|ORMException $e) {
                return 'FromAcceptedToStartCheck:ERROR: '.$e;
            }
        }
        return 'FromAcceptedToStartCheck:SUCCESS';
    }

    private function fromStartedToEnd($today): string
    {
        $bookings = [];
        $bookings = array_merge($bookings, $this->bookingRepository->findStartedToEnd($today));
        $bookings = array_merge($bookings, $this->bookingRepository->findAcceptedToEnd($today));
        foreach ($bookings as $booking) {
            if($booking->getWorkflow()->getConfiguration()['autoEnd'] === true) {
                $workflow = $this->workflows->get($booking, 'booking_instance');
                if($workflow->can($booking, 'end_booking')){
                    $workflow->apply($booking, 'end_booking');
                } else {
                    $workflow->apply($booking, 'start_booking');
                }
            } else {
                if($booking->getStatus() === 'progress')
                    $booking->setDateEnd(new DateTime());
            }

            try {
                $this->bookingRepository->add($booking, false);
            } catch (OptimisticLockException|ORMException $e) {
                return 'fromStartedToEnd:ERROR: '.$e;
            }
        }

        // Flush all changes at the end of the loop
        try {
            $this->entityManager->flush();
        } catch (OptimisticLockException|ORMException $e) {
            return 'fromStartedToEnd:ERROR: '.$e;
        }
        return 'fromStartedToEnd:SUCCESS';
    }

    private function fromInitToAccept(): string
    {
        $bookings = $this->bookingRepository->findInit();
        foreach ($bookings as $booking) {
            $workflow = $this->workflows->get($booking, 'booking_instance');
            if($booking->getWorkflow()->getConfiguration()['autoValidation'] === true){
                $workflow->apply($booking, 'accepted_auto_silent');
            } else {
                $workflow->apply($booking, 'waiting_moderation');
            }
            try {
                $this->bookingRepository->add($booking, true);
            } catch (OptimisticLockException|ORMException $e) {
                return 'FromInitToAcceptedCheck:ERROR: '.$e;
            }
        }
        return 'FromInitToAcceptedCheck:SUCCESS';
    }
}
