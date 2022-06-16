<?php

declare(strict_types=1);

namespace PeibinLaravel\ConfigCenter;

use PeibinLaravel\ConfigCenter\Contracts\Driver;

class DriverFactory
{
    public function create(string $driver, array $properties = []): Driver
    {
        $defaultDriver = config('config_center.driver', '');
        $config = config('config_center.drivers.' . $driver, []);
        $class = $config['driver'] ?? $defaultDriver;
        $instance = app($class, $config);
        foreach ($properties as $method => $value) {
            if (method_exists($instance, $method)) {
                $instance->{$method}($value);
            }
        }
        return $instance;
    }
}
