<?php

declare(strict_types=1);

namespace Nacos;

use GuzzleHttp\RequestOptions;
use Nacos\Utils\Codec\Json;

class Operator extends AbstractNacos
{
    public function getSwitches(): array
    {
        $response = $this->request('GET', '/nacos/v1/ns/operator/switches');

        return Json::decode((string) $response->getBody());
    }

    public function updateSwitches($entry, $value, bool $debug = false): array
    {
        $debug = $debug ? 'true' : 'false';
        $params = compact('entry', 'value', 'debug');

        $response = $this->request('PUT', '/nacos/v1/ns/operator/switches', [
            RequestOptions::QUERY => $params,
        ]);

        return Json::decode((string) $response->getBody());
    }

    public function getMetrics(): array
    {
        $response = $this->request('GET', '/nacos/v1/ns/operator/metrics');

        return Json::decode((string) $response->getBody());
    }

    public function getServers($healthy = true): array
    {
        $healthy = $healthy ? 'true' : 'false';
        $params = compact('healthy');

        $response = $this->request('GET', '/nacos/v1/ns/operator/servers', [
            RequestOptions::QUERY => $params,
        ]);

        return Json::decode((string) $response->getBody());
    }

    public function getLeader(): array
    {
        $response = $this->request('GET', '/nacos/v1/ns/raft/leader');

        return Json::decode((string) $response->getBody());
    }
}
