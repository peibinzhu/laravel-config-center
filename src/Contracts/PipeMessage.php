<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter\Contracts;

interface PipeMessage
{
    public function getData(): array;
}
