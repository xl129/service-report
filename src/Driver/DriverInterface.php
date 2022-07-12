<?php

namespace YuanxinHealthy\ServiceReport\Driver;

use YuanxinHealthy\ServiceReport\Entity\ServiceEntity;

interface DriverInterface
{
    /**
     * 循环
     * @author xionglin
     */
    public function creatReportLoop();

    /**
     * 获取服务信息
     * @return ServiceEntity
     * @author xionglin
     */
    public function getServiceEntity(): ServiceEntity;

    /**
     * 上报接口
     * @param ServiceEntity $entity
     * @author xionglin
     */
    public function report(ServiceEntity $entity);

    /**
     * @return  ServiceEntity[]
     * @author xionglin
     */
    public function getList(): array;
}
