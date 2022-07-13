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

    public string $createdAt = '';

    public string $url = '';

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
        $this->createdAt = date('Y-m-d H:i:s');

        try {
            $this->host = Network::ip();
            $this->url = sprintf('http://%s:%s', $this->host, $this->port);
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
     * @param string $createdAt
     * @return ServiceEntity
     */
    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @param string $url
     * @return ServiceEntity
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return array
     * @author xionglin
     */
    public function toArray(): array
    {
        return [
            'host'       => $this->host,
            'port'       => $this->port,
            'created_at' => $this->createdAt,
            'app_name'   => $this->appName,
            'app_env'    => $this->appEnv,
            'url'        => $this->url,
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
}
