<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter\Contracts;

interface DriverInterface
{
    public function fetchConfig();

    public function createMessageFetcherLoop(): void;

    public function onPipeMessage(PipeMessageInterface $pipeMessage): void;
}
