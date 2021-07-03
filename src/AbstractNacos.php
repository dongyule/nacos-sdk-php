<?php

declare(strict_types=1);

namespace Nacos;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Nacos\Exception\RequestException as NacosRequestException;
use Nacos\Exception\NotFoundException;
use Nacos\Exception\ConnectionException;

abstract class AbstractNacos
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var integer
     */
    protected $port;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var integer
     */
    protected $timeout = 3;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var callable
     */
    protected $handler;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
    }

    public function request($method, $uri, array $options = [])
    {
        if (!isset($options['timeout'])) {
            $options['timeout'] = $this->timeout;
        }
        try {
            $resp = $this->client()->request($method, $uri, $options);
        } catch (ConnectException $connectException) {
            throw new ConnectionException("[Nacos Server] " . $connectException->getMessage());
        } catch (RequestException $exception) {
            throw new NacosRequestException($exception->getMessage());
        }
        return $resp;
    }

    public function getServerUri(): string
    {
        $url = $this->config['url'] ?? '';
        if ($url) {
            $this->url = $url;
            return $url;
        }
        $this->host = $this->config['host'] ?? '127.0.0.1';
        $this->port = $this->config['port'] ?? 8848;
        return sprintf('%s:%d', $this->host, $this->port);
    }

    public function client(): Client
    {
        return new Client([
            'base_uri' => $this->getServerUri(),
            'timeout' => $this->timeout,
            'handler' => $this->handler,
            RequestOptions::HEADERS => [
                'charset' => 'UTF-8',
            ],
        ]);
    }
}
