<?php

namespace YuanxinHealthy\ServiceReport\Entity;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\Network;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class ServiceEntity
{
    public string $host = '';

    public int $port = 0;

    public string $appName = '';

    public string $appEnv = '';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(?ContainerInterface $container)
    {
        if (is_null($container)) {
            return;
        }

        $config = $container->get(ConfigInterface::class);
        $this->appName = strval($config->get('app_name'));
        $this->appEnv = strval($config->get('app_env'));
        $arr = explode(':', $config->get('server.settings.admin_server', ''));
        $this->port = intval(end($arr));

        try {
            $this->host = Network::ip();
        } catch (Throwable $e) {
            unset($e);
        }
    }

    /**
     * @param string $host
     * @return ServiceEntity
     */
    public function setHost(string $host): self
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param int $port
     * @return ServiceEntity
     */
    public function setPort(int $port): self
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param string $appName
     * @return ServiceEntity
     */
    public function setAppName(string $appName): self
    {
        $this->appName = $appName;
        return $this;
    }

    /**
     * @param string $appEnv
     * @return ServiceEntity
     */
    public function setAppEnv(string $appEnv): self
    {
        $this->appEnv = $appEnv;
        return $this;
    }

    /**
     * @return array
     * @author xionglin
     */
    public function toArray(): array
    {
        return [
            'host'    => $this->host,
            'port'    => $this->port,
            'appName' => $this->appName,
            'appEnv'  => $this->appEnv,
        ];
    }

    /**
     * @return string
     * @author xionglin
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public function getName(): string
    {
        return sprintf("%s:%s", $this->appName, $this->appEnv);
    }
}
