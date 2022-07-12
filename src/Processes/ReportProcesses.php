<?php

declare(strict_types=1);

namespace YuanxinHealthy\ServiceReport\Processes;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\ProcessManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Swoole\Server;
use YuanxinHealthy\ServiceReport\Mode;
use YuanxinHealthy\ServiceReport\Driver\DriverFactory;

class ReportProcesses extends AbstractProcess
{
    public $name = 'service-report';

    /**
     * @var Server
     */
    protected Server $server;

    /**
     * @var ConfigInterface
     */
    protected mixed $config;

    /**
     * @var DriverFactory
     */
    protected DriverFactory $driverFactory;


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->config = $container->get(ConfigInterface::class);
        $this->driverFactory = $container->get(DriverFactory::class);
    }

    public function bind($server): void
    {
        $this->server = $server;
        parent::bind($server);
    }

    public function isEnable($server): bool
    {
        $arr = explode(':', strval($this->config->get('server.settings.admin_server', '')));

        return $server instanceof Server
            && $this->config->get('service_report.enable', false)
            && strtolower($this->config->get('service_report.mode', Mode::PROCESS)) === Mode::PROCESS
            && (intval(end($arr)) > 0);
    }

    public function handle(): void
    {
        $driver = $this->config->get('service_report.driver', '');
        if (!$driver) {
            return;
        }

        $instance = $this->driverFactory->create($driver, $this->server);

        if (is_null($instance)) {
            return;
        }

        // 起一个进程拉去定时上报
        $instance->creatReportLoop();

        while (ProcessManager::isRunning()) {
            sleep(1);
        }
    }
}
