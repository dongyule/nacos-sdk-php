<?php

declare(strict_types=1);

namespace Nacos;

use GuzzleHttp\RequestOptions;
use Hyperf\LoadBalancer\LoadBalancerManager;
use Hyperf\LoadBalancer\Node;
use Nacos\Model\InstanceModel;
use Nacos\Model\ServiceModel;
use Nacos\Utils\Codec\Json;

class Instance extends AbstractNacos
{
    public function register(InstanceModel $instanceModel): bool
    {
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

        return $this->loadBalancer($enabled, 'random');
    }

    public function detail(InstanceModel $instanceModel): array
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

        return (string) $response->getBody() === 'ok';
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

    protected function loadBalancer(array $nodes, $tactics = 'random')
    {
        $loadNodes = [];
        $nacosNodes = [];
        /** @var array|InstanceModel $node */
        foreach ($nodes as $node) {
            if (is_array($node)) {
                $node = (object) $node;
            }
            $loadNodes[] = new Node($node->ip, $node->port, (int) $node->weight);
            $key = sprintf('%s:%d', $node->ip, $node->port);
            $nacosNodes[$key] = $node;
        }

        $loadBalancerManager = new LoadBalancerManager();
        /** @var \Hyperf\LoadBalancer\LoadBalancerInterface $loadBalancer */
        $loadBalancer = $loadBalancerManager->get($tactics);
        $loadBalancer->setNodes($loadNodes);

        /** @var Node $availableNode */
        $availableNode = $loadBalancer->select();

        $key = sprintf('%s:%d', $availableNode->host, $availableNode->port);
        return $nacosNodes[$key];
    }
}
