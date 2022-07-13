<?php

declare(strict_types=1);

namespace YuanxinHealthy\ServiceReport\Driver;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use YuanxinHealthy\ServiceReport\Client\NacosClient;
use Hyperf\Nacos\Application;
use YuanxinHealthy\ServiceReport\Entity\ServiceEntity;

class NacosDriver extends AbstractDriver
{
    protected string $driverName = 'nacos';

    protected Application $client;

    // 是否创建服务
    protected bool $serviceCreated = false;

    // 是否创建实例
    protected bool $instanceCreated = false;

    protected bool $lightBeatEnabled = false;

    protected string $namespaceId;

    protected string $groupName;

    protected string $serviceName;

    protected string $serverEphemeralName;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->client = $container->get(NacosClient::class);
        $config = $container->get(ConfigInterface::class);
        $this->namespaceId = $config->get('service_report.drivers.nacos.namespace_id');
        $this->groupName = $config->get('service_report.drivers.nacos.group_name');
        $this->serviceName = strval($config->get('service_report.drivers.nacos.service_name', 'service_list'));
        $this->serverEphemeralName = strval(
            $config->get('service_report.drivers.nacos.service_ephemeral_name', 'service_ephemeral_list')
        );
    }

    /**
     * 服务上报
     *
     * @param ServiceEntity $entity
     * @return void
     * @author xionglin
     */
    public function report(ServiceEntity $entity): void
    {
        if (!$this->isServiceCreated()) {
            $this->createService();
        }

        if (!$this->isInstanceCreated($entity)) {
            $this->createInstance($entity);
        }

        // 发送心跳
        if ($this->instanceCreated) {
            $this->beat($entity);
        }
    }

    /**
     * 检查服务是否创建
     * @return bool
     * @author xionglin
     */
    protected function isServiceCreated(): bool
    {
        if ($this->serviceCreated) {
            return true;
        }

        $response = $this->client->service->detail(
            $this->serviceName,
            $this->groupName,
            $this->namespaceId
        );

        if ($response->getStatusCode() === 404) {
            return false;
        }

        if ($response->getStatusCode() === 500 && strpos((string)$response->getBody(), 'not found') > 0) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $this->serviceCreated = true;
        return true;
    }

    /**
     * 创建实列
     * @return void
     * @author xionglin
     */
    protected function createService(): void
    {
        $response = $this->client->service->create($this->serviceName, [
            'groupName'        => $this->groupName,
            'namespaceId'      => $this->namespaceId,
            'metadata'         => [],
            'protectThreshold' => 0,
        ]);

        if ($response->getStatusCode() == 200) {
            $this->serviceCreated = true;
            return;
        }

        if ($response->getStatusCode() == 500 && strpos((string)$response->getBody(), 'already exists') > 0) {
            $this->serviceCreated = true;
        }
    }

    /**
     * 检查服务是否创建
     * @param ServiceEntity $entity
     * @return bool
     * @author xionglin
     */
    protected function isInstanceCreated(ServiceEntity $entity): bool
    {
        if ($this->instanceCreated) {
            return true;
        }

        $response = $this->client->instance->detail(
            $entity->host,
            $entity->port,
            $this->serverEphemeralName,
            [
                'groupName'   => $this->groupName,
                'namespaceId' => $this->namespaceId,
            ]
        );

        if ($this->isNoIpsFound($response)) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $this->instanceCreated = true;
        return true;
    }

    /**
     * 创建实列
     * @param ServiceEntity $entity
     * @return void
     * @author xionglin
     */
    protected function createInstance(ServiceEntity $entity): void
    {
        $response = $this->client->instance->register(
            $entity->host,
            $entity->port,
            $this->serverEphemeralName,
            [
                'groupName'   => $this->groupName,
                'namespaceId' => $this->namespaceId,
                'ephemeral'   => 'true',
                'metadata'    => $entity->toJson(),
            ]
        );

        if ($response->getStatusCode() !== 200 || (string)$response->getBody() !== 'ok') {
            return;
        }

        $this->instanceCreated = true;
    }

    /**
     * 发送心跳
     * @param ServiceEntity $entity
     * @return void
     * @author xionglin
     */
    protected function beat(ServiceEntity $entity): void
    {
        $name = $this->serverEphemeralName;
        $response = $this->client->instance->beat(
            $name,
            [
                'ip'          => $entity->host,
                'port'        => $entity->port,
                'serviceName' => $this->groupName . '@@' . $name,
            ],
            $this->groupName,
            $this->namespaceId,
            null,
            $this->lightBeatEnabled
        );

        $result = json_decode($response->getBody()->getContents(), true);
        if (isset($result['lightBeatEnabled'])) {
            $this->lightBeatEnabled = $result['lightBeatEnabled'];
        }
        if (isset($result['code']) && $result['code'] == 20404) {
            $this->instanceCreated = false;
            $this->lightBeatEnabled = false;
            $this->createInstance($entity);
        }
    }

    /**
     * 检查
     * @param ResponseInterface $response
     * @return bool
     * @author xionglin
     */
    protected function isNoIpsFound(ResponseInterface $response): bool
    {
        if ($response->getStatusCode() === 404) {
            return true;
        }

        if ($response->getStatusCode() === 500) {
            $messages = [
                'no ips found',
                'no matched ip',
            ];
            $body = (string)$response->getBody();
            foreach ($messages as $message) {
                if (str_contains($body, $message)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return  ServiceEntity[]
     * @author xionglin
     */
    public function getList(): array
    {
        try {
            $response = $this->client->instance->list($this->serverEphemeralName, [
                'groupName'   => $this->groupName,
                'namespaceId' => $this->namespaceId,
                'healthyOnly' => 'true',
            ]);

            if ($response->getStatusCode() != 200) {
                return [];
            }

            $result = json_decode($response->getBody()->getContents(), true);
            if (empty($result['hosts']) || !is_array($result['hosts'])) {
                return [];
            }

            $list = [];

            foreach ($result['hosts'] as $host) {
                if (empty($host['metadata']['host']) || empty($host['metadata']['port'])) {
                    continue;
                }

                $entity = (new ServiceEntity(null))
                    ->setAppName($host['metadata']['app_name'] ?? '')
                    ->setAppEnv($host['metadata']['app_env'] ?? '')
                    ->setCreateAt($host['metadata']['create_at'] ?? '')
                    ->setHost($host['metadata']['host'])
                    ->setPort(intval($host['metadata']['port']));

                $list[] = $entity;
            }

            return $list;
        } catch (Throwable $e) {
            $this->logger->warning($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [];
        }
    }
}
