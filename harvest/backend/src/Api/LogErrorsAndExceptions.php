<?php

namespace Costlocker\Integrations\Api;

use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Symfony\Component\Debug\ErrorHandler;
use Silex\Provider\MonologServiceProvider;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RavenHandler;
use Raven_Client;

class LogErrorsAndExceptions implements ServiceProviderInterface
{
    private $logsDir;

    public function __construct($logsDir)
    {
        $this->logsDir = $logsDir;
    }

    public function register(Container $app)
    {
        ErrorHandler::register();

        $app->register(
            new MonologServiceProvider(),
            [
                'monolog.logfile' => $this->getLogFile('app'),
                'monolog.level' => Logger::NOTICE,
                'monolog.dsn' => getenv('APP_SENTRYLOG_DSN'),
                'monolog.handler' => function (Application $app) {
                    $dsn = $app['monolog.dsn'];
                    if (!$dsn) {
                        return new StreamHandler($app['monolog.logfile']);
                    }
                    $level = MonologServiceProvider::translateLevel($app['monolog.level']);
                    return new RavenHandler(
                        new Raven_Client($app['monolog.dsn'], [
                            'environment' => getenv('APP_ENV'),
                            'tags' => [
                                'app' => 'harvest',
                                'php_version' => phpversion(),
                            ],
                        ]),
                        $level,
                        $app['monolog.bubble']
                    );
                }
            ]
        );

        $app['monolog.import'] = function ($app) {
            $handlers = [
                new StreamHandler($this->getLogFile('import'))
            ];
            if ($app['monolog.dsn']) {
                $handlers[] = $app['monolog.handler'];
            }
            return new Logger('import', $handlers);
        };
    }

    private function getLogFile($file)
    {
        $env = getenv('APP_ENV');
        return "{$this->logsDir}/{$env}-{$file}.log";
    }
}
