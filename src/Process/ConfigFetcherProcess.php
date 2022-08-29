<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter\Process;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use PeibinLaravel\ConfigCenter\Contracts\Driver;
use PeibinLaravel\ConfigCenter\DriverFactory;
use PeibinLaravel\ConfigCenter\Mode;
use PeibinLaravel\Contracts\StdoutLoggerInterface;
use PeibinLaravel\Process\AbstractProcess;
use PeibinLaravel\Process\ProcessManager;
use Swoole\Http\Server;

class ConfigFetcherProcess extends AbstractProcess
{
    public string $name = 'config-center-fetcher';

    /**
     * @var Server
     */
    protected $server;

    public function __construct(protected Container $container)
    {
        parent::__construct($container);
        $this->config = $container->get(Repository::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->driverFactory = $container->get(DriverFactory::class);
    }


    public function bind($server): void
    {
        $this->server = $server;
        parent::bind($server);
    }

    public function isEnable($server): bool
    {
        return $server instanceof Server
            && $this->config->get('config_center.enable', false)
            && strtolower($this->config->get('config_center.mode', Mode::PROCESS)) === Mode::PROCESS;
    }

    public function handle(): void
    {
        $driver = $this->config->get('config_center.driver', '');
        if (!$driver) {
            return;
        }

        /** @var Driver $instance */
        $instance = $this->container->get(DriverFactory::class)->create($driver, [
            'setServer' => $this->server,
        ]);

        $instance->createMessageFetcherLoop();

        while (ProcessManager::isRunning()) {
            sleep(1);
        }
    }
}
