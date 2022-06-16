<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter\Contracts;

interface Driver
{
    public function fetchConfig();

    public function createMessageFetcherLoop(): void;

    public function onPipeMessage(PipeMessage $pipeMessage): void;
}
