<?php

namespace Costlocker\Integrations\Database;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshAccessTokensCommand extends Command
{
    private $usecase;

    public function __construct(RefreshAccessTokens $uc)
    {
        parent::__construct();
        $this->usecase = $uc;
    }

    protected function configure()
    {
        $this
            ->setName('refreshTokens')
            ->setDescription('Refresh OAuth2 token')
            ->addOption('expiration', 'e', InputOption::VALUE_OPTIONAL, null, '1 hours')
            ->addOption('execute', 'x', InputOption::VALUE_NONE, 'By default dry ryn mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mode = $input->getOption('execute') ? 'Refresh tokens' : 'Find expired tokens';
        $output->writeln([
            "<info>Mode:</info> <comment>{$mode}</comment>",
        ]);

        $tokens = $this->usecase->__invoke(
            $input->getOption('expiration'),
            $input->getOption('execute')
        );

        $output->writeln([
            '<info>Expired active accounts</info>',
            json_encode($tokens, JSON_PRETTY_PRINT),
        ]);
    }
}
