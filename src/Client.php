<?php

declare(strict_types=1);

namespace Nacos;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Nacos\Exceptions\NacosConfigNotFound;
use Nacos\Exceptions\NacosConnectionException;
use Nacos\Exceptions\NacosRequestException;
use Nacos\Exceptions\NacosResponseException;

class Client
{
    const DEFAULT_PORT = 8848;
    const DEFAULT_TIMEOUT = 3;

    const DEFAULT_GROUP = 'DEFAULT_GROUP';

    const WORD_SEPARATOR = "\x02";
    const LINE_SEPARATOR = "\x01";

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var int
     */
    protected $timeout = self::DEFAULT_TIMEOUT;

    /**
     * __construct function
     *
     * @param string $host
     * @param int $port
     */
    public function __construct(string $host, int $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @param string $namespace
     * @return self
     */
    public function setNamespace(string $namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @desc: request function
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    protected function request(string $method, string $uri, array $options = [])
    {
        if (!isset($options['timeout'])) {
            $options['timeout'] = $this->timeout;
        }

        $client = new GuzzleHttpClient([
            'base_uri' => "http://{$this->host}:{$this->port}",
            'timeout' => $this->timeout
        ]);

        try {
            $resp = $client->request($method, $uri, $options);
        } catch (ConnectException $connectException) {
            throw new NacosConnectionException("[Nacos Server] " . $connectException->getMessage());
        } catch (RequestException $exception) {
            throw new NacosRequestException($exception->getMessage());
        }
        return $resp;
    }

    /**
     * @desc: assertResponse
     * @param \Psr\Http\Message\ResponseInterface $resp
     * @param $expected
     * @param $message
     * @return bool
     */
    protected function assertResponse(\Psr\Http\Message\ResponseInterface $resp, $expected, $message)
    {
        $actual = $resp->getBody()->getContents();
        if ($expected !== $actual) {
            throw new NacosResponseException("$message, actual: {$actual}");
        }
        return true;
    }

}
