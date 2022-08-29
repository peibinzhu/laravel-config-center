<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter\Listeners;

use PeibinLaravel\ConfigCenter\Mode;

class CreateMessageFetcherLoopListener extends OnPipeMessageListener
{
    public function handle(object $event): void
    {
        $mode = strtolower($this->config->get('config_center.mode', Mode::PROCESS));
        if ($mode === Mode::COROUTINE) {
            $instance = $this->createDriverInstance();
            $instance && $instance->createMessageFetcherLoop();
        }
    }
}
