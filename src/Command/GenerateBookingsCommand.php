<?php

namespace App\Command;

use App\Entity\Booking;
use App\Repository\CatalogueResourceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Workflow\Registry;

#[AsCommand(
    name: 'app:generate-bookings',
    description: '[TEST ONLY] Generate bookings to test actuators',
)]
class GenerateBookingsCommand extends Command
{
    public function __construct(private CatalogueResourceRepository $catalogueResourceRepository, private UserRepository $userRepository, private EntityManagerInterface $manager,private Registry $workflows)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('bookings', InputArgument::REQUIRED, 'Number of bookings to generate')
            ->addArgument('catalogue', InputArgument::REQUIRED, 'Catalogue id')
            ->addArgument('usernames', InputArgument::IS_ARRAY, 'List of usernames')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $catalogue = $this->catalogueResourceRepository->find($input->getArgument("catalogue"));
        $users = $this->userRepository->findBy(["username"=>$input->getArgument("usernames")]);
        $faker = Factory::create("fr_FR");

        $startDate = new \DateTime();
        for($i=1;$i<$input->getArgument("bookings");$i++)
        {
            $booking = new Booking();
            $booking->setNumber(1);
            $booking->addUser($faker->randomElement($users));
            $booking->setCatalogueResource($catalogue);
            $booking->setDateStart($startDate);
            $endDate = clone $startDate;
            $endDate->modify("+1 hour");
            $booking->setDateEnd($endDate);
            $booking->setStatus("init");
            $booking->addResource($faker->randomElement($catalogue->getResource()->getValues()));

            $this->manager->persist($booking);
            $workflow = $this->workflows->get($booking, 'booking_instance');
            $workflow->apply($booking, 'accepted_auto');
            $startDate = $endDate;
        }

        $this->manager->flush();

        return Command::SUCCESS;
    }
}
