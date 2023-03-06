<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter;

use Illuminate\Console\Events\ArtisanStarting;
use Illuminate\Support\ServiceProvider;
use PeibinLaravel\ConfigCenter\Listeners\CreateMessageFetcherLoopListener;
use PeibinLaravel\ConfigCenter\Listeners\FetchConfigOnBootListener;
use PeibinLaravel\ConfigCenter\Listeners\OnPipeMessageListener;
use PeibinLaravel\ConfigCenter\Process\ConfigFetcherProcess;
use PeibinLaravel\Process\Events\PipeMessage as UserProcessPipeMessage;
use PeibinLaravel\ProviderConfig\Contracts\ProviderConfigInterface;
use PeibinLaravel\SwooleEvent\Events\BeforeWorkerStart;
use PeibinLaravel\SwooleEvent\Events\OnPipeMessage;

class ConfigCenterServiceProvider extends ServiceProvider implements ProviderConfigInterface
{
    public function __invoke(): array
    {
        return [
            'processes' => [
                ConfigFetcherProcess::class,
            ],
            'listeners' => [
                BeforeWorkerStart::class      => [
                    CreateMessageFetcherLoopListener::class,
                    FetchConfigOnBootListener::class,
                ],
                ArtisanStarting::class        => FetchConfigOnBootListener::class,
                OnPipeMessage::class          => OnPipeMessageListener::class,
                UserProcessPipeMessage::class => OnPipeMessageListener::class,
            ],
            'publish'   => [
                [
                    'id'          => 'config-center',
                    'source'      => __DIR__ . '/../config/config_center.php',
                    'destination' => config_path('config_center.php'),
                ],
            ],
        ];
    }
}
