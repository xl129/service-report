<?php

declare(strict_types=1);

namespace YuanxinHealthy\ServiceReport;

use YuanxinHealthy\ServiceReport\Client\NacosClient;
use YuanxinHealthy\ServiceReport\Client\NacosClientFactory;
use YuanxinHealthy\ServiceReport\Processes\ReportProcesses;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                NacosClient::class => NacosClientFactory::class,
            ],
            'processes'    => [
                ReportProcesses::class,
            ],
            'listeners'    => [
            ],
            'annotations'  => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish'      => [
                [
                    'id'          => 'service-report',
                    'description' => '服务上报',
                    'source'      => __DIR__ . '/../publish/service_report.php',
                    'destination' => BASE_PATH . '/config/autoload/service_report.php',
                ],
            ],
            'server'       => [
                'settings' => [
                    'admin_server' => '0.0.0.0:9502',
                ],
            ],
        ];
    }
}
