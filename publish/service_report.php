<?php

use YuanxinHealthy\ServiceReport\Mode;
use YuanxinHealthy\ServiceReport\Driver\NacosDriver;

return [
    // 应用端可设置成false，只获取列表
    'enable'  => (bool)env('SERVICE_REPORT_ENABLE', true),
    'driver'  => env('SERVICE_REPORT_DRIVER', 'nacos'),
    'mode'    => env('SERVICE_REPORT_MODE', Mode::PROCESS),
    'drivers' => [
        'nacos' => [
            'driver'                 => NacosDriver::class,
            'interval'               => 5,
            'namespace_id'           => env('SERVICE_REPORT_NACOS_NAMESPACE', 'service-report'),
            'group_name'             => env('SERVICE_REPORT_NACOS_GROUP', 'default'),
            'service_name'           => env('SERVICE_REPORT_NACOS_SERVICE_NAME', 'service_list'),
            // 服务名称
            'service_ephemeral_name' => env('SERVICE_REPORT_NACOS_SERVICE_EPHEMERAL_NAME', 'service_ephemeral_list'),
            // 临时服务列表
            'client'                 => [
                'host'     => env('NACOS_HOST', '127.0.0.1'),
                'port'     => env('NACOS_PORT', 8848),
                'username' => env('NACOS_USERNAME', 'nacos'),
                'password' => env('NACOS_PASSWORD', 'nacos'),
                'guzzle'   => [
                    'config' => null,
                ],
            ],
        ],
    ],
];