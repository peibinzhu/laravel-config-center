<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;
use PeibinLaravel\ConfigCenter\Contracts\ClientInterface;
use PeibinLaravel\ConfigCenter\Contracts\DriverInterface;
use PeibinLaravel\ConfigCenter\Contracts\PipeMessageInterface;
use PeibinLaravel\ConfigCenter\Events\ConfigUpdated;
use PeibinLaravel\Contracts\StdoutLoggerInterface;
use PeibinLaravel\Coordinator\Constants;
use PeibinLaravel\Coordinator\CoordinatorManager;
use PeibinLaravel\Coroutine\Coroutine;
use PeibinLaravel\Process\ProcessCollector;
use Psr\Log\LoggerInterface;
use Swoole\Http\Server;
use Swoole\Process;

abstract class AbstractDriver implements DriverInterface
{
    protected ?Server $server;

    protected Repository $config;

    protected LoggerInterface $logger;

    protected ClientInterface $client;

    protected ?string $pipeMessage = PipeMessage::class;

    protected string $driverName = '';

    public function __construct(protected Container $container)
    {
        $this->logger = $this->container->get(StdoutLoggerInterface::class);
        $this->config = $this->container->get(Repository::class);
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
                        $this->syncConfig($config, $prevConfig);
                    }
                    $prevConfig = $config;
                }
            }, $interval);
        });
    }

    public function fetchConfig()
    {
        if (method_exists($this->client, 'pull')) {
            $config = $this->pull();
            $config && is_array($config) && $this->updateConfig($config);
        }
    }

    public function onPipeMessage(PipeMessageInterface $pipeMessage): void
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

    protected function event(object $event)
    {
        $this->container->get(Dispatcher::class)?->dispatch($event);
    }

    protected function syncConfig(array $config, ?array $prevConfig = null)
    {
        if (class_exists(ProcessCollector::class) && !ProcessCollector::isEmpty()) {
            $this->shareConfigToProcesses($config);
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
                $prevValue = $this->config->get($key);
                $this->config->set($key, $value);
                $this->event(new ConfigUpdated($key, $value, $prevValue));
                $this->logger->debug(sprintf('Config [%s] is updated.', $key));
            }
        }
    }

    protected function getInterval(): int
    {
        return (int)$this->config->get('config_center.drivers.' . $this->driverName . '.interval', 5);
    }

    protected function shareConfigToProcesses(array $config): void
    {
        $pipeMessage = $this->pipeMessage;
        $message = new $pipeMessage($config);
        if (!$message instanceof PipeMessageInterface) {
            throw new InvalidArgumentException('Invalid pipe message object.');
        }
        $this->shareMessageToWorkers($message);
        $this->shareMessageToUserProcesses($message);
    }

    protected function shareMessageToWorkers(PipeMessageInterface $message): void
    {
        if ($this->server instanceof Server) {
            $workerCount = $this->server->setting['worker_num'] + ($this->server->setting['task_worker_num'] ?? 0) - 1;
            for ($workerId = 0; $workerId <= $workerCount; ++$workerId) {
                $this->server->sendMessage($message, $workerId);
            }
        }
    }

    protected function shareMessageToUserProcesses(PipeMessageInterface $message): void
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
