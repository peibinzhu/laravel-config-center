<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter;

use PeibinLaravel\ConfigCenter\Contracts\PipeMessageInterface;

class PipeMessage implements PipeMessageInterface
{
    public function __construct(protected array $data)
    {
    }

    public function getData(): array
    {
        return $this->data;
    }
}
