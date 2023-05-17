<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use PeibinLaravel\ConfigCenter\Listeners\CreateMessageFetcherLoopListener;
use PeibinLaravel\ConfigCenter\Listeners\FetchConfigOnBootListener;
use PeibinLaravel\ConfigCenter\Listeners\OnPipeMessageListener;
use PeibinLaravel\ConfigCenter\Process\ConfigFetcherProcess;
use PeibinLaravel\Process\Events\PipeMessage as UserProcessPipeMessage;
use PeibinLaravel\SwooleEvent\Events\BeforeWorkerStart;
use PeibinLaravel\SwooleEvent\Events\BootApplication;
use PeibinLaravel\SwooleEvent\Events\OnPipeMessage;

class ConfigCenterServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerConfig();
        $this->registerListeners();
        $this->registerPublishing();
    }

    private function registerConfig()
    {
        $this->app->get(Repository::class)->push('processes', ConfigFetcherProcess::class);
    }

    private function getListeners(): array
    {
        return [
            BootApplication::class        => [
                FetchConfigOnBootListener::class,
            ],
            BeforeWorkerStart::class      => [
                CreateMessageFetcherLoopListener::class,
                FetchConfigOnBootListener::class,
            ],
            OnPipeMessage::class          => [
                OnPipeMessageListener::class,
            ],
            UserProcessPipeMessage::class => [
                OnPipeMessageListener::class,
            ],
        ];
    }

    private function registerListeners()
    {
        $dispatcher = $this->app->get(Dispatcher::class);
        foreach ($this->getListeners() as $event => $_listeners) {
            foreach ((array)$_listeners as $listener) {
                $dispatcher->listen($event, $listener);
            }
        }
    }

    public function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config_center.php' => config_path('config_center.php'),
            ], 'config-center');
        }
    }
}
