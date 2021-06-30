<?php

declare(strict_types=1);

namespace Nacos;

use GuzzleHttp\Client as GuzzleHttpClient;
use Nacos\Exceptions\NacosNamingNoAliveInstance;
use Nacos\Exceptions\NacosNamingNotFound;
use Nacos\Models\BeatInfo;
use Nacos\Models\BeatResult;
use Nacos\Models\ServiceInstance;
use Nacos\Models\ServiceInstanceList;
use Nacos\Utils\RandomByWeightSelector;

class Instance extends Client
{
    /**
     * 注册一个实例到服务
     * @param ServiceInstance $instance
     * @return boolean
     */
    public function register(ServiceInstance $instance)
    {
        $instance->validate();
        $resp = $this->request('POST', '/nacos/v1/ns/instance', ['form_params' => $instance->toCreateParams()]);
        return $this->assertResponse($resp, 'ok', "Nacos Client create service instance fail");
    }

    /**
     * 删除服务下的一个实例
     * @param string $serviceName
     * @param string $ip
     * @param int $port
     * @param string|null $clusterName
     * @param string|null $namespaceId
     * @return boolean
     */
    public function delete(
        string $serviceName,
        string $ip,
        int $port,
        string $clusterName = null,
        string $namespaceId = null
    ) {
        $query = array_filter(compact('serviceName', 'ip', 'port', 'clusterName', 'namespaceId'));
        $resp = $this->request('DELETE', '/nacos/v1/ns/instance', ['query' => $query]);
        return $this->assertResponse($resp, 'ok', "Nacos Client delete service instance fail");
    }

    /**
     * @desc: 更新服务下的一个实例
     * @param ServiceInstance $instance
     * @return bool
     */
    public function update(ServiceInstance $instance)
    {
        $instance->validate();
        $resp = $this->request('PUT', '/nacos/v1/ns/instance', ['form_params' => $instance->toUpdateParams()]);
        return $this->assertResponse($resp, 'ok', "Nacos Client update service instance fail");
    }

    /**
     * 查询服务下的实例列表
     *
     * @param string $serviceName 服务名
     * @param string|null $namespaceId 命名空间ID
     * @param array $clusters 集群名称
     * @param bool $healthyOnly 是否只返回健康实例
     * @return ServiceInstanceList
     */
    public function list(
        string $serviceName,
        string $namespaceId = null,
        array $clusters = [],
        bool $healthyOnly = false
    ) {
        if (empty($namespaceId)) {
            $namespaceId = $this->namespace;
        }

        $query = array_filter([
            'serviceName' => $serviceName,
            'namespaceId' => $namespaceId,
            'clusters' => implode(',', $clusters),
            'healthyOnly' => $healthyOnly,
        ]);

        $resp = $this->request('GET', '/nacos/v1/ns/instance/list', [
            'http_errors' => false,
            'query' => $query,
        ]);

        $data = json_decode((string)$resp->getBody(), true);

        if (404 === $resp->getStatusCode()) {
            throw new NacosNamingNotFound(
                "service not found: $serviceName",
                404
            );
        }
        return new ServiceInstanceList($data);
    }

    /**
     * 查询一个服务下个某个实例详情
     *
     * @param string $serviceName      服务名
     * @param string $ip               实例IP
     * @param int $port                实例端口
     * @param string|null $namespaceId 命名空间 id
     * @param string|null $cluster     集群名称
     * @param bool $healthyOnly        是否只返回健康实例
     * @return ServiceInstance
     */
    public function detail(
        string $serviceName,
        string $ip,
        int $port,
        string $namespaceId = null,
        string $cluster = null,
        bool $healthyOnly = false
    ) {
        if (empty($namespaceId)) {
            $namespaceId = $this->namespace;
        }
        $query = array_filter(compact(
            'serviceName',
            'ip',
            'port',
            'namespaceId',
            'cluster',
            'healthyOnly'
        ));

        $resp = $this->request('GET', '/nacos/v1/ns/instance', ['query' => $query]);
        $data = json_decode((string)$resp->getBody(), true);
        $data['serviceName'] = $data['service'];

        return new ServiceInstance($data);
    }

    /**
     * 发送实例心跳
     * @param string $serviceName
     * @param BeatInfo $beat
     * @return BeatResult
     */
    public function beat(string $serviceName, BeatInfo $beat)
    {
        $formParams = [
            'serviceName' => $serviceName,
            'beat' => json_encode($beat)
        ];

        $resp = $this->request('PUT', '/nacos/v1/ns/instance/beat', ['form_params' => $formParams]);
        $array = json_decode((string) $resp->getBody(), true);

        $result = new BeatResult();
        $result->clientBeatInterval = $array['clientBeatInterval'];
        return $result;
    }

    /**
     * select
     *
     * @param string $serviceName
     * @param string $namespaceId
     *
     * @return ServiceInstance
     * @throws \Exception
     */
    public function select(string $serviceName, string $namespaceId = null): ServiceInstance
    {
        if (empty($namespaceId)) {
            $namespaceId = $this->namespace;
        }
        $list = $this->list($serviceName, $namespaceId);

        if (count($list->hosts) === 0) {
            throw new NacosNamingNoAliveInstance("$serviceName no alive instnace");
        }

        return RandomByWeightSelector::select($list->hosts);
    }

    /**
     * call
     *
     * @param $serviceName
     * @param $method
     * @param $uri
     * @param array $options
     * @param string $namespaceId
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function call($serviceName, $method, $uri, $options = [], $namespaceId = null): \Psr\Http\Message\ResponseInterface
    {
        if (empty($namespaceId)) {
            $namespaceId = $this->namespace;
        }
        $instance = $this->select($serviceName, $namespaceId);
        $client = new GuzzleHttpClient([
            'base_uri' => "http://{$instance->ip}:{$instance->port}",
            'timeout' => $this->timeout
        ]);
        return $client->request($method, $uri, $options);
    }

}
