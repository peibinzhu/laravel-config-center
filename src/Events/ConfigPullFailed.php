<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter\Events;

use Throwable;

class ConfigPullFailed
{
    public function __construct(public Throwable $throwable, public ?string $key = null, public ?array $item = null)
    {
    }
}
