<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use PeibinLaravel\ConfigCenter\Contracts\Driver;

class DriverFactory
{
    public function __construct(protected Container $container, protected Repository $config)
    {
    }

    public function create(string $driver, array $properties = []): Driver
    {
        $defaultDriver = $this->config->get('config_center.driver', '');
        $config = $this->config->get('config_center.drivers.' . $driver, []);
        $class = $config['driver'] ?? $defaultDriver;
        $instance = $this->container->make($class, $config);
        foreach ($properties as $method => $value) {
            if (method_exists($instance, $method)) {
                $instance->{$method}($value);
            }
        }
        return $instance;
    }
}
