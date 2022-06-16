<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter;

use PeibinLaravel\ConfigCenter\Contracts\PipeMessage as PipeMessageContract;

class PipeMessage implements PipeMessageContract
{
    public function __construct(protected array $data)
    {
    }

    public function getData(): array
    {
        return $this->data;
    }
}
