<?php

namespace App\Command;

use App\Service\RemoteService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:test-service:mailer',
    description: '[TEST ONLY] A command to test the mail service',
)]
class TestServiceMailerCommand extends Command
{
    public function __construct(private readonly RemoteService $remoteService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('to', InputArgument::REQUIRED, 'Send mail to this address')
        ;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('to');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
            $data = $this->remoteService->send('mailer/send', [], ['to' => [$arg1], 'subject' => 'test', 'body' => "test"], 'POST');
            dd($data);
            $io->success('Mail sent to: ' . $data);
        }

        $io->success('Mail sent successfully!');

        return Command::SUCCESS;
    }

}
