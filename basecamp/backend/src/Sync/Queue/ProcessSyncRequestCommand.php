<?php

namespace Costlocker\Integrations\Sync\Queue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

class ProcessSyncRequestCommand extends Command
{
    private $usecase;
    private $logger;

    public function __construct(ProcessSyncRequest $uc, LoggerInterface $l)
    {
        parent::__construct();
        $this->usecase = $uc;
        $this->logger = $l;
    }

    protected function configure()
    {
        $this
            ->setName('queue:event')
            ->setDescription('Process request event')
            ->addOption('id', 'i', InputOption::VALUE_REQUIRED, 'Event id');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $eventId = $input->getOption('id');
        try {
            $output->writeln("<comment>Process event</comment> {$eventId}");
            $projectsCount = $this->usecase->__invoke($eventId);
            $output->writeln("<info>Synchronized projects</info> {$projectsCount}");
        } catch (\Exception $e) {
            $output->writeln([
                "<error>{$e->getMessage()}</error>",
                get_class($e),
            ]);
            $this->logger->critical($e);
        }
    }
}
