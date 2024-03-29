<?php

namespace PeibinLaravel\ConfigCenter\Listeners;

use Illuminate\Contracts\Config\Repository;
use PeibinLaravel\ConfigCenter\Contracts\DriverInterface;
use PeibinLaravel\ConfigCenter\Contracts\PipeMessageInterface;
use PeibinLaravel\ConfigCenter\DriverFactory;
use PeibinLaravel\Process\Events\PipeMessage as UserProcessPipeMessage;
use PeibinLaravel\SwooleEvent\Events\OnPipeMessage;

class OnPipeMessageListener
{
    public function __construct(
        protected DriverFactory $driverFactory,
        protected Repository $config
    ) {
    }

    public function handle(object $event): void
    {
        if ($instance = $this->createDriverInstance()) {
            if ($event instanceof OnPipeMessage || $event instanceof UserProcessPipeMessage) {
                $event->data instanceof PipeMessageInterface && $instance->onPipeMessage($event->data);
            }
        }
    }

    protected function createDriverInstance(): ?DriverInterface
    {
        if (!$this->config->get('config_center.enable', false)) {
            return null;
        }

        $driver = $this->config->get('config_center.driver', '');
        if (!$driver) {
            return null;
        }
        return $this->driverFactory->create($driver);
    }
}
