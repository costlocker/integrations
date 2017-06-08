<?php

namespace Costlocker\Integrations\Sync\Queue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessSyncRequestsCommand extends Command
{
    private $usecase;

    /** @var OutputInterface */
    private $output;
    private $isVerboseMode;
    private $isInfiniteLoop = true;

    public function __construct(ProcessSyncRequest $uc)
    {
        parent::__construct();
        $this->usecase = $uc;
    }

    protected function configure()
    {
        $this
            ->setName('queue')
            ->setDescription('Process synchronization requests')
            ->addOption('delay', 'd', InputOption::VALUE_OPTIONAL, 'Delay in milliseconds', 1000);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->isVerboseMode = $this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL;

        $delayInMillis = $input->getOption('delay');
        $delayInMicros = $delayInMillis * 1000;
        $this->writeln([
            'Enable converting errors to exceptions',
            'Starting infinite loop...',
            '',
        ]);
        do {
            $this->executeCommand();
            usleep($delayInMicros);
        } while ($this->isInfiniteLoop);
    }

    public function executeCommand()
    {
        try {
            $processedEvents = $this->usecase->__invoke($this);
            $this->writeln("<comment>Processed events</comment> {$processedEvents}", $processedEvents > 0);
        } catch (\Exception $e) {
            $this->writeln([
                "<error>{$e->getMessage()}</error>",
                get_class($e),
            ]);
            $this->isInfiniteLoop = false;
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
