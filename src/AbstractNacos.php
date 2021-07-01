<?php

declare(strict_types=1);

namespace Nacos;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

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

    public function request($method, $uri, array $options = [])
    {
        return $this->client()->request($method, $uri, $options);
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
            'handler' => $this->handler,
            RequestOptions::HEADERS => [
                'charset' => 'UTF-8',
            ],
        ]);
    }
}
