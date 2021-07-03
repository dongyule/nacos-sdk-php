<?php

declare(strict_types=1);

namespace Nacos;

use GuzzleHttp\RequestOptions;
use Nacos\Model\ConfigModel;
use Nacos\Utils\Codec\Json;

class Config extends AbstractNacos
{
    public function get(ConfigModel $configModel, $toArray = false)
    {
        $response = $this->request('GET', '/nacos/v1/cs/configs', [
            RequestOptions::QUERY => $configModel->toArray(),
        ]);

        $statusCode = $response->getStatusCode();
        $contents = (string) $response->getBody();
        if ($statusCode !== 200) {
            if ($toArray) {
                return [];
            }
            return null;
        }

        if ($toArray) {
            return $configModel->parse($contents);
        }

        return $contents;
    }

    public function set(ConfigModel $configModel)
    {
        $response = $this->request('POST', '/nacos/v1/cs/configs', [
            RequestOptions::FORM_PARAMS => $configModel->toArray(),
        ]);
        return (string) $response->getBody() === 'true';
    }

    public function delete(ConfigModel $configModel)
    {
        $response = $this->request('DELETE', '/nacos/v1/cs/configs', [
            RequestOptions::QUERY => $configModel->toArray(),
        ]);

        return (string) $response->getBody() === 'true';
    }

    /**
     * 监听配置
     * @param ConfigModel[] $configs
     * @param int $timeout 长轮训等待事件，默认 30 ，单位：秒
     * @return ConfigModel[]
     */
    public function listen(array $configs, int $timeout = 30): array
    {
        $char1 = pack('C*', 1);
        $char2 = pack('C*', 2);
        $configStringList = [];
        foreach ($configs as $cache) {
            $items = [$cache->dataId, $cache->group, md5($cache->content)];
            if ($cache->tenant) {
                $items[] = $cache->tenant;
            }
            $configStringList[] = join($char2, $items);
        }
        $configString = join($char1, $configStringList) . $char1;
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
        $lines = explode($char1, urldecode($respString));
        foreach ($lines as $line) {
            if (!empty($line)) {
                $parts = explode($char2, $line);
                $c = new ConfigModel();
                if (count($parts) === 3) {
                    list($c->dataId, $c->group, $c->tenant) = $parts;
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

}
