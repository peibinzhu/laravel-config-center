<?php

namespace PeibinLaravel\ConfigCenter\Listeners;

use App\Events\OnPipeMessage;
use PeibinLaravel\ConfigCenter\Contracts\Driver;
use PeibinLaravel\ConfigCenter\Contracts\PipeMessage;
use PeibinLaravel\ConfigCenter\DriverFactory;
use PeibinLaravel\Process\Events\PipeMessage as UserProcessPipeMessage;

class OnPipeMessageListener
{
    public function __construct(protected DriverFactory $driverFactory)
    {
    }

    public function handle(object $event): void
    {
        if ($instance = $this->createDriverInstance()) {
            if ($event instanceof OnPipeMessage || $event instanceof UserProcessPipeMessage) {
                $event->data instanceof PipeMessage && $instance->onPipeMessage($event->data);
            }
        }
    }

    protected function createDriverInstance(): ?Driver
    {
        if (
            !config('config_center.enable', false) ||
            !($driver = config('config_center.driver', ''))
        ) {
            return null;
        }

        return $this->driverFactory->create($driver);
    }
}
