<?php

declare(strict_types=1);

namespace Nacos;

use GuzzleHttp\RequestOptions;
use Nacos\Model\InstanceModel;
use Nacos\Model\ServiceModel;
use Nacos\Utils\Codec\Json;
use Nacos\Utils\RandomByWeightSelector;

class Instance extends AbstractNacos
{
    public function register(InstanceModel $instanceModel): bool
    {
        if (is_array($instanceModel->metadata)) {
            $instanceModel->metadata = json_encode($instanceModel->metadata);
        }
        $response = $this->request('POST', '/nacos/v1/ns/instance', [
            RequestOptions::QUERY => $instanceModel->toArray(),
        ]);

        return (string) $response->getBody() === 'ok';
    }

    public function delete(InstanceModel $instanceModel): bool
    {
        $response = $this->request('DELETE', '/nacos/v1/ns/instance', [
            RequestOptions::QUERY => $instanceModel->toArray(),
        ]);

        return (string) $response->getBody() === 'ok';
    }

    public function update(InstanceModel $instanceModel): bool
    {
        if (is_array($instanceModel->metadata)) {
            $instanceModel->metadata = json_encode($instanceModel->metadata);
        }
        $instanceModel->healthy = null;
        $response = $this->request('PUT', '/nacos/v1/ns/instance', [
            RequestOptions::QUERY => $instanceModel->toArray(),
        ]);

        return (string) $response->getBody() === 'ok';
    }

    public function list(ServiceModel $serviceModel, array $clusters = [], ?bool $healthyOnly = null): array
    {
        $serviceName = $serviceModel->serviceName;
        $groupName = $serviceModel->groupName;
        $namespaceId = $serviceModel->namespaceId;
        $params = array_filter(compact('serviceName', 'groupName', 'namespaceId', 'clusters', 'healthyOnly'), function ($item) {
            return $item !== null;
        });
        if (isset($params['clusters'])) {
            $params['clusters'] = implode(',', $params['clusters']);
        }

        $response = $this->request('GET', '/nacos/v1/ns/instance/list', [
            RequestOptions::QUERY => $params,
        ]);

        return Json::decode((string) $response->getBody());
    }

    public function getOptimal(ServiceModel $serviceModel, array $clusters = [])
    {
        $list = $this->list($serviceModel, $clusters, true);
        $instance = $list['hosts'] ?? [];
        if (! $instance) {
            return false;
        }
        $enabled = array_filter($instance, function ($item) {
            return $item['enabled'] && $item['healthy'];
        });
        if ($enabled) {
            $instances = [];
            foreach ($enabled as $node) {
                $instances[] = new Model\InstanceModel($node);
            }
        }

        return RandomByWeightSelector::select($instances);
    }

    public function detail(InstanceModel $instanceModel) : array
    {
        $response = $this->request('GET', '/nacos/v1/ns/instance', [
            RequestOptions::QUERY => $instanceModel->toArray(),
        ]);

        return Json::decode((string) $response->getBody());
    }

    public function beat(ServiceModel $serviceModel, InstanceModel $instanceModel)
    {
        $serviceName = $serviceModel->serviceName;
        $groupName = $serviceModel->groupName;
        $ephemeral = $instanceModel->ephemeral;
        $namespaceId = $instanceModel->namespaceId;
        $params = array_filter(compact('serviceName', 'groupName', 'ephemeral', 'namespaceId'), function ($item) {
            return $item !== null;
        });
        $params['beat'] = $instanceModel->toJson();
        $response = $this->request('PUT', '/nacos/v1/ns/instance/beat', [
            RequestOptions::QUERY => $params,
        ]);
        return Json::decode((string) $response->getBody());
    }

    public function updateHealth(InstanceModel $instanceModel): bool
    {
        if ($instanceModel->healthy === null) {
            $instanceModel->healthy = true;
        }

        $response = $this->request('PUT', '/nacos/v1/ns/health/instance', [
            RequestOptions::QUERY => $instanceModel->toArray(),
        ]);

        return (string) $response->getBody() === 'ok';
    }

}
