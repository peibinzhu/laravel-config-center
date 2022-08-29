<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use PeibinLaravel\ConfigCenter\Contracts\Client as ClientContract;
use PeibinLaravel\ConfigCenter\Contracts\Driver as DriverContract;
use PeibinLaravel\ConfigCenter\Contracts\PipeMessage as PipeMessageContract;
use PeibinLaravel\ConfigCenter\Events\ConfigUpdated;
use PeibinLaravel\Contracts\StdoutLoggerInterface;
use PeibinLaravel\Process\ProcessCollector;
use PeibinLaravel\Process\Utils\Constants;
use PeibinLaravel\Process\Utils\CoordinatorManager;
use Swoole\Coroutine;
use Swoole\Http\Server;
use Swoole\Process;

abstract class AbstractDriver implements DriverContract
{
    protected ?Server $server;

    protected ClientContract $client;

    protected ?string $pipeMessage = PipeMessage::class;

    protected string $driverName = '';

    protected StdoutLoggerInterface $logger;

    protected Repository $config;

    protected Dispatcher $event;

    public function __construct(protected Container $container)
    {
        $this->logger = $this->container->get(StdoutLoggerInterface::class);
        $this->config = $this->container->get(Repository::class);
        $this->event = $this->container->get(Dispatcher::class);
    }

    public function createMessageFetcherLoop(): void
    {
        Coroutine::create(function () {
            $interval = $this->getInterval();
            retry(INF, function () use ($interval) {
                $prevConfig = [];
                while (true) {
                    $coordinator = CoordinatorManager::until(Constants::WORKER_EXIT);
                    $workerExited = $coordinator->yield($interval);
                    if ($workerExited) {
                        break;
                    }

                    $config = $this->pull();
                    if ($config !== $prevConfig) {
                        $this->syncConfig($config);
                    }
                    $prevConfig = $config;
                }
            }, $interval, fn () => false);
        });
    }

    public function fetchConfig()
    {
        if (method_exists($this->client, 'pull')) {
            $config = $this->pull();
            $config && $this->updateConfig($config);
        }
    }

    public function onPipeMessage(PipeMessageContract $pipeMessage): void
    {
        $this->updateConfig($pipeMessage->getData());
    }

    public function getServer(): ?Server
    {
        return $this->server;
    }

    public function setServer($server): AbstractDriver
    {
        $this->server = $server;
        return $this;
    }

    protected function syncConfig(array $config)
    {
        if (class_exists(ProcessCollector::class) && !ProcessCollector::isEmpty()) {
            $this->shareConfigToProcesses($config);

            // Update the current process configuration.
            $this->updateConfig($config);
        } else {
            $this->updateConfig($config);
        }
    }

    protected function pull(): array
    {
        return $this->client->pull();
    }

    protected function updateConfig(array $config)
    {
        foreach ($config as $key => $value) {
            if (is_string($key)) {
                $this->config->set($key, $value);
                $this->dispatchEvent($key, $value);
                $this->logger->debug(sprintf('Config [%s] is updated.', $key));
            }
        }
    }

    protected function dispatchEvent(string $key, mixed $value)
    {
        $this->event->dispatch(new ConfigUpdated($key, $value));
    }

    protected function getInterval(): int
    {
        return (int)$this->config->get('config_center.drivers.' . $this->driverName . '.interval', 5);
    }

    protected function shareConfigToProcesses(array $config): void
    {
        $pipeMessage = $this->pipeMessage;
        $message = new $pipeMessage($config);
        if (!$message instanceof PipeMessageContract) {
            throw new \InvalidArgumentException('Invalid pipe message object.');
        }
        $this->shareMessageToWorkers($message);
        $this->shareMessageToUserProcesses($message);
    }

    protected function shareMessageToWorkers(PipeMessageContract $message): void
    {
        if ($this->server instanceof Server) {
            $workerCount = $this->server->setting['worker_num'] + ($this->server->setting['task_worker_num'] ?? 0) - 1;
            for ($workerId = 0; $workerId <= $workerCount; ++$workerId) {
                $this->server->sendMessage($message, $workerId);
            }
        }
    }

    protected function shareMessageToUserProcesses(PipeMessageContract $message): void
    {
        $processes = ProcessCollector::all();
        if ($processes) {
            $string = serialize($message);
            /** @var Process $process */
            foreach ($processes as $process) {
                $result = $process->exportSocket()->send($string, 10);
                if ($result === false) {
                    $this->logger->error('Configuration synchronization failed. Please restart the server.');
                }
            }
        }
    }
}
