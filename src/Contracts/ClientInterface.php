<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter\Contracts;

interface ClientInterface
{
    /**
     * Pull the config values from configuration center, and then update the Config values.
     */
    public function pull(): array;
}
