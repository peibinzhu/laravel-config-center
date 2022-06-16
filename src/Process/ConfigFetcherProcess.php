<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter\Process;

use PeibinLaravel\ConfigCenter\Contracts\Driver;
use PeibinLaravel\ConfigCenter\DriverFactory;
use PeibinLaravel\ConfigCenter\Mode;
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

    public function bind($server): void
    {
        $this->server = $server;
        parent::bind($server);
    }

    public function isEnable($server): bool
    {
        return $server instanceof Server
            && config('config_center.enable', false)
            && strtolower(config('config_center.mode', Mode::PROCESS)) === Mode::PROCESS;
    }

    public function handle(): void
    {
        $driver = config('config_center.driver', '');
        if (!$driver) {
            return;
        }

        /** @var Driver $instance */
        $instance = app(DriverFactory::class)->create($driver, [
            'setServer' => $this->server,
        ]);

        $instance->createMessageFetcherLoop();

        while (ProcessManager::isRunning()) {
            sleep(1);
        }
    }
}
