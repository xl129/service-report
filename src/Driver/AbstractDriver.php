<?php

namespace YuanxinHealthy\ServiceReport\Driver;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use Hyperf\Utils\Coroutine;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use YuanxinHealthy\ServiceReport\Entity\ServiceEntity;
use Throwable;

abstract class AbstractDriver implements DriverInterface
{
    protected ContainerInterface $container;

    protected StdoutLoggerInterface $logger;

    protected ConfigInterface $config;

    protected string $driverName = '';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $this->container->get(StdoutLoggerInterface::class);
        $this->config = $this->container->get(ConfigInterface::class);
    }

    public function creatReportLoop()
    {
        Coroutine::create(function () {
            $interval = $this->getInterval();
            retry(INF, function () use ($interval) {
                while (true) {
                    try {
                        $coordinator = CoordinatorManager::until(Constants::WORKER_EXIT);
                        $workerExited = $coordinator->yield($interval);
                        if ($workerExited) {
                            break;
                        }

                        $entity = $this->getServiceEntity();

                        $this->report($entity);
                    } catch (Throwable $e) {
                        $this->logger->error($e->getMessage(), [
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'code' => $e->getCode(),
                        ]);
                    }
                }
            }, $interval * 1000);
        });
    }


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getServiceEntity(): ServiceEntity
    {
        return $this->container->get(ServiceEntity::class);
    }

    /**
     * 上报接口
     * @param ServiceEntity $entity
     * @author xionglin
     */
    public abstract function report(ServiceEntity $entity);

    /**
     * 列表接口
     * @return  ServiceEntity[]
     * @author xionglin
     */
    public abstract function getList(): array;

    /**
     * 定时心跳时间
     * @return int
     */
    protected function getInterval(): int
    {
        return (int)$this->config->get('service_report.drivers.' . $this->driverName . '.interval', 5);
    }
}
