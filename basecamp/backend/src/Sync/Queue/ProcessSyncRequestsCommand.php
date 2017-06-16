<?php

namespace Costlocker\Integrations\Sync\Queue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

class ProcessSyncRequestsCommand extends Command
{
    private $processRequests;
    private $agreggateWebhooks;
    private $logger;

    /** @var OutputInterface */
    private $output;
    private $isVerboseMode;
    private $isInfiniteLoop = true;
    private $loop = 0;

    public function __construct(ProcessSyncRequests $p, AggregateBasecampWebhooks $w, LoggerInterface $l)
    {
        parent::__construct();
        $this->processRequests = $p;
        $this->agreggateWebhooks = $w;
        $this->logger = $l;
    }

    protected function configure()
    {
        $this
            ->setName('queue:daemon')
            ->setDescription('Process synchronization requests')
            ->addOption('delay', 'd', InputOption::VALUE_OPTIONAL, 'Delay in milliseconds', 1000)
            ->addOption('basecampDelay', 'b', InputOption::VALUE_OPTIONAL, 'Delay before processing BC webhook', 5);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->isVerboseMode = $this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL;

        $delayInMillis = $input->getOption('delay');
        $delayInMicros = $delayInMillis * 1000;
        $basecampDelay = $input->getOption('basecampDelay');
        $this->writeln([
            "<comment>Daemon delay</comment>: {$delayInMillis}ms",
            "<comment>Basecamp webhook delay</comment>: {$basecampDelay}s",
            'Starting infinite loop...',
            '',
        ]);
        do {
            try {
                $this->processSyncRequests();
                $this->aggregateBasecampWebhooks($basecampDelay);
            } catch (\Exception $e) {
                $this->writeln([
                    "<comment>Loop</comment>: {$this->loop}",
                    "<error>{$e->getMessage()}</error>",
                    get_class($e),
                ]);
                $this->isInfiniteLoop = false;
                $this->logger->critical($e);
            }
            usleep($delayInMicros);
            $this->loop++;
        } while ($this->isInfiniteLoop);
    }

    private function processSyncRequests()
    {
        $processedEvents = $this->processRequests->__invoke(function ($eventId, $status) {
            $this->writeln("<comment>{$eventId}</comment> {$status}");
        });
        if (!$processedEvents) {
            $this->writeln('No events available', false);
        }
    }

    private function aggregateBasecampWebhooks($delay)
    {
        if ($this->loop % 4 != 0) {
            $this->writeln("Skip basecamp webhooks", false);
            return;
        }
        $events = $this->agreggateWebhooks->__invoke($delay);
        if ($events) {
            $this->writeln([
                '<comment>Aggregated basecamp webhooks</comment>',
                json_encode($events)
            ]);
        } else {
            $this->writeln("No basecamp webhook available", false);
        }
    }

    private function writeln($data, $isAlwaysPrinted = true)
    {
        if ($isAlwaysPrinted || $this->isVerboseMode) {
            $this->output->writeln('<info>' . date('c') . '</info>');
            $this->output->writeln($data);
        }
    }
}
