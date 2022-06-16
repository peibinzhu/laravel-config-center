<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter\Listeners;

class FetchConfigOnBootListener extends OnPipeMessageListener
{
    public function handle(object $event): void
    {
        $instance = $this->createDriverInstance();
        $instance && $instance->fetchConfig();
    }
}
