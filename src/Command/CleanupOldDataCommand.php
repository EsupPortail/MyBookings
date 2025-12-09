<?php

namespace App\Command;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\StatisticsRepository;
use App\Repository\UserRepository;
use App\Repository\WorkflowLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:cleanup:old-data',
    description: 'Cleans up old data from the database',
)]
class CleanupOldDataCommand extends Command
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private WorkflowLogRepository $workflowLogRepository,
        private StatisticsRepository $statisticsRepository,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        #[Autowire('%env(LIMIT_CLEANUP_OLD_DATA)%')] private int $limitCleanupOldData,
        #[Autowire('%env(LIMIT_CLEANUP_OLD_DATA_STATISTICS)%')] private int $limitCleanupOldDataForStatistics
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if($this->limitCleanupOldData <= 0 || $this->limitCleanupOldDataForStatistics <= 0) {
            $io->warning('Skipping cleanup. Both LIMIT_CLEANUP_OLD_DATA and LIMIT_CLEANUP_OLD_DATA_STATISTICS are set to 0 or negative.');
            return Command::FAILURE;
        }

        // Définir la date limite (ex: 1 année en arrière)
        $limitDate = new \DateTimeImmutable("-$this->limitCleanupOldData months");

        // Logique de nettoyage des données anciennes sur les réservations
        $bookings = $this->bookingRepository->getBookingsBefore($limitDate);
        $deletedBookings = $this->deleteBookings($bookings);
        $io->success(sprintf('Deleted %d old bookings.', count($deletedBookings)));

        //Logique de nettoyage des utilisateurs anciens
        $users = $this->userRepository->getUserLastLoginBefore($limitDate);
        $this->removeUsers($users);
        $io->success(sprintf('Deleted %d old users.', count($users)));

        // Logique de nettoyage des données anciennes sur les statistiques
        $statistics = $this->statisticsRepository->getStatisticsBefore(new \DateTimeImmutable("-$this->limitCleanupOldDataForStatistics months"));
        $this->removeStatistics($statistics);
        $io->success(sprintf('Deleted %d old statistics.', count($statistics)));

        $this->entityManager->flush();
        return Command::SUCCESS;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function deleteBookings(array $bookings): array
    {
        $deletedBookings = [];
        foreach ($bookings as $booking) {
            $this->removeLogs($booking);
            $this->bookingRepository->remove($booking);
            $deletedBookings[] = $booking->getId();
        }
        return $deletedBookings;
    }

    private function removeLogs(Booking $booking): void
    {
        $workflowLogs = $this->workflowLogRepository->findBy(['booking' => $booking]);
        foreach ($workflowLogs as $log) {
            $this->workflowLogRepository->remove($log);
        }
    }

    private function removeStatistics(array $statistics): void
    {
        foreach ($statistics as $statistic) {
            $this->statisticsRepository->remove($statistic);
        }
    }

    private function removeUsers(array $users): void
    {
        foreach ($users as $user) {
            $this->userRepository->remove($user);
        }
    }
}
