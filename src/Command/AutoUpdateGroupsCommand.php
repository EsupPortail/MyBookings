<?php

namespace App\Command;

use App\Service\GroupServiceInterface;
use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:auto-update-groups',
    description: 'Update the groups according to the provider and the query set in the db',
)]
class AutoUpdateGroupsCommand extends Command
{
    public function __construct(private readonly GroupServiceInterface $groupService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('group', null, InputOption::VALUE_REQUIRED, 'Group id (optional)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('group'))
            $this->groupService->updateGroups($input->getOption('group'));
        else
            $this->groupService->updateGroups();

        $now = new DateTime();
        $day = $now->format('d-m-y H:i');
        $output->writeln($day. ' : GROUPS UPDATED');
        return Command::SUCCESS;
    }
}
