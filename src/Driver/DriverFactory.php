<?php

namespace YuanxinHealthy\ServiceReport\Driver;

use Hyperf\Contract\ConfigInterface;

class DriverFactory
{
    /**
     * @var ConfigInterface
     */
    protected ConfigInterface $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * 创建驱动
     *
     * @param string $driver
     * @return DriverInterface|null
     * @author xionglin
     */
    public function create(string $driver): ?DriverInterface
    {
        $config = $this->config->get('service_report.drivers.' . $driver, []);
        if (empty($config['driver'])) {
            return null;
        }

        $class = $config['driver'];
        /** @var DriverInterface $instance */
        $instance = make($class);

        return $instance;
    }
}
