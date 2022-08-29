<?php

use PeibinLaravel\ConfigCenter\Mode;
use PeibinLaravel\ConfigNacos\NacosDriver;

return [
    // 是否开启配置中心
    'enable'  => env('CONFIG_CENTER_ENABLE', true),
    // 使用的驱动类型，对应同级别配置 drivers 下的 key
    'driver'  => env('CONFIG_CENTER_DRIVER', 'nacos'),
    // 配置中心的运行模式，多进程模型推荐使用 PROCESS 模式，单进程模型推荐使用 COROUTINE 模式
    'mode'    => env('CONFIG_CENTER_MODE', Mode::PROCESS),
    'drivers' => [
        'nacos' => [
            'driver'          => NacosDriver::class,
            'interval'        => 3,
            'default_key'     => 'nacos_config',
            'listener_config' => [],
            'client'          => [
                // nacos server url like https://nacos.io, Priority is higher than host:port
                // 'uri' => '',
                'host'     => '127.0.0.1',
                'port'     => 8848,
                'username' => null,
                'password' => null,
                'guzzle'   => [
                    'config' => null,
                ],
            ],
        ],
    ],
];
