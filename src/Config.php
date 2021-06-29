<?php

declare(strict_types=1);

namespace Nacos;

use Nacos\Exceptions\NacosConfigNotFound;
use Nacos\Exceptions\NacosRequestException;
use Nacos\Exceptions\NacosResponseException;
use \UnexpectedValueException;
use Nacos\Utils\PropertiesConfigParser;
use Nacos\Models\Config as ConfigModel;

class Config extends Client
{
    /**
     * Get Config Option
     *
     * @param string $dataId
     * @param string $group
     * @return string
     * @throws NacosConfigNotFound
     */
    public function get(string $dataId, string $group = Client::DEFAULT_GROUP)
    {
        $query = [
            'dataId' => $dataId,
            'group' => $group
        ];

        if ($this->namespace) {
            $query['tenant'] = $this->namespace;
        }

        $resp = $this->request('GET', '/nacos/v1/cs/configs', [
            'http_errors' => false,
            'query' => $query
        ]);

        if (404 === $resp->getStatusCode()) {
            throw new NacosConfigNotFound(
                "config not found, dataId:{$dataId} group:{$group} tenant:{$this->namespace}",
                404
            );
        }
        return $resp->getBody()->getContents();
    }

    /**
     * @desc: publish Config
     * @param string $dataId
     * @param string $group
     * @param string $content
     * @return bool|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function set(string $dataId, string $group, string $content)
    {
        $formParams = [
            'dataId' => $dataId,
            'group' => $group,
            'content' => $content
        ];

        if ($this->namespace) {
            $formParams['tenant'] = $this->namespace;
        }

        $resp = $this->request('POST', '/nacos/v1/cs/configs', ['form_params' => $formParams]);
        return $this->assertResponse($resp, 'true', "Nacos Client publish config fail");
    }

    /**
     * removeConfig
     *
     * @param string $dataId
     * @param string $group
     * @return string
     * @throws NacosRequestException
     */
    public function delete(string $dataId, string $group = Client::DEFAULT_GROUP)
    {
        $query = [
            'dataId' => $dataId,
            'group' => $group,
        ];

        if ($this->namespace) {
            $query['tenant'] = $this->namespace;
        }

        $resp = $this->request('DELETE', '/nacos/v1/cs/configs', ['query' => $query]);
        return $this->assertResponse($resp, 'true', "Nacos Client delete config fail");
    }

    /**
     * 监听配置
     * @param ConfigModel[] $configs
     * @param int $timeout 长轮训等待事件，默认 30 ，单位：秒
     * @return Config[]
     */
    public function listen(array $configs, int $timeout = 30): array
    {
        $configStringList = [];
        foreach ($configs as $cache) {
            $items = [$cache->dataId, $cache->group, $cache->contentMd5];
            if ($cache->namespace) {
                $items[] = $cache->namespace;
            }
            $configStringList[] = join(Client::WORD_SEPARATOR, $items);
        }
        $configString = join(Client::LINE_SEPARATOR, $configStringList) . Client::LINE_SEPARATOR;

        $resp = $this->request('POST', '/nacos/v1/cs/configs/listener', [
            'timeout' => $timeout + $this->timeout,
            'headers' => ['Long-Pulling-Timeout' => $timeout * 1000],
            'form_params' => [
                'Listening-Configs' => $configString,
            ],
        ]);

        $respString = $resp->getBody()->getContents();
        if (!$respString) {
            return [];
        }

        $changed = [];
        $lines = explode(self::LINE_SEPARATOR, urldecode($respString));
        foreach ($lines as $line) {
            if (!empty($line)) {
                $parts = explode(self::WORD_SEPARATOR, $line);
                $c = new ConfigModel();
                if (count($parts) === 3) {
                    list($c->dataId, $c->group, $c->namespace) = $parts;
                } elseif (count($parts) === 2) {
                    list($c->dataId, $c->group) = $parts;
                } else {
                    continue;
                }
                $changed[] = $c;
            }
        }
        return $changed;
    }

    /**
     * @desc: 获取配置内容并解析(现仅支持 properties 格式)
     * @param string $dataId
     * @param string $group
     * @param string $format
     * @return array
     */
    public function getParsedConfigs(
        string $dataId,
        string $group = Client::DEFAULT_GROUP,
        string $format = 'properties'
    ) {
        $content = $this->get($dataId, $group);

        if (!$format) {
            $format = array_slice(explode('.', $dataId), -1)[0];
        }

        if ($format === 'properties') {
            return PropertiesConfigParser::parse($content);
        }
        throw new UnexpectedValueException('Format not supported');
    }

}
