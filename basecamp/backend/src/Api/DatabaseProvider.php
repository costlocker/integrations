<?php

namespace Costlocker\Integrations\Api;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Provider\DoctrineServiceProvider;
use Knp\Provider\ConsoleServiceProvider;
use Symfony\Component\Console\Output\ConsoleOutput;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Dflydev\Provider\DoctrineOrm\DoctrineOrmServiceProvider;

/** @SuppressWarnings(PHPMD.CouplingBetweenObjects) */
class DatabaseProvider implements ServiceProviderInterface
{
    private $projectDir;

    public function __construct($projectDir)
    {
        $this->projectDir = $projectDir;
    }
    
    public function register(Container $app)
    {
        $app->register(new DoctrineServiceProvider, [
            'db.options' => [
                'driver' => 'pdo_pgsql',
                'host' => getenv('DB_HOST'),
                'port' => getenv('DB_PORT') ?: 5432,
                'dbname' => getenv('DB_NAME'),
                'user' => getenv('DB_USER'),
                'password' => getenv('DB_PASS'),
                'charset' => 'utf8',
            ],
        ]);
        $app->register(new DoctrineOrmServiceProvider, [
            'orm.proxies_dir' => "{$this->projectDir}/var/proxies",
            'orm.strategy.naming' => new \Doctrine\ORM\Mapping\UnderscoreNamingStrategy(),
            'orm.em.options' => [
                'mappings' => [
                    [
                        'type' => 'annotation',
                        'namespace' => \Costlocker\Integrations\Entities::class,
                        'use_simple_annotation_reader' => false,
                        'path' => "{$this->projectDir}/src/Entities",
                    ],
                ],
            ],
        ]);

        if (PHP_SAPI == 'cli') {
            $this->loadDevelopersTools($app);
        }
    }

    private function loadDevelopersTools(Container $app)
    {
        $app->register(new ConsoleServiceProvider(), array(
            'console.name' => 'Costlocker + Basecamp',
            'console.version' => '1.0.0',
            'console.project_directory' => $this->projectDir,
        ));

        $output = new ConsoleOutput();
        $config = new Configuration(
            $app['db'],
            new OutputWriter(function ($message) use ($output) {
                $output->writeln($message);
            })
        );
        $config->setMigrationsTableName('db_migrations');
        $config->setMigrationsNamespace(\Costlocker\Integrations\Database\Migrations::class);
        $config->setMigrationsDirectory("{$this->projectDir}/app/migrations");
        $config->registerMigrationsFromDirectory("{$this->projectDir}/app/migrations");

        $app['console']->getHelperSet()->set(new ConnectionHelper($app['db']), 'db');
        $app['console']->getHelperSet()->set(new ConfigurationHelper($app['db'], $config), 'configuration');
        $app['console']->getHelperSet()->set(new EntityManagerHelper($app['orm.em']), 'em');
        
        $app['console']->addCommands([
            // Migrations
            new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand(),
            new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand(),
            new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
            new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
            new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand(),
            new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
            new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand(),
            // DBAL
            new \Doctrine\DBAL\Tools\Console\Command\RunSqlCommand(),
            new \Doctrine\DBAL\Tools\Console\Command\ImportCommand(),
            // ORM
            new \Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand(),
            new \Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand(),
            new \Doctrine\ORM\Tools\Console\Command\ClearCache\QueryCommand(),
            new \Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand(),
            new \Doctrine\ORM\Tools\Console\Command\InfoCommand(),
            new \Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand(),
        ]);
    }
}
