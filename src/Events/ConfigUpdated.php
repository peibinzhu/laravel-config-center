<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter\Events;

class ConfigUpdated
{
    public function __construct(public string $key, public mixed $current, public mixed $previous)
    {
    }
}
