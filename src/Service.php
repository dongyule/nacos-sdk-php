<?php

declare(strict_types=1);

namespace Nacos;

use Nacos\Exceptions\NacosConfigNotFound;
use Nacos\Exceptions\NacosRequestException;
use Nacos\Exceptions\NacosResponseException;
use \UnexpectedValueException;
use Nacos\Utils\PropertiesConfigParser;
use Nacos\Models\Service as ServiceModel;

class Service extends Client
{
    /**
     * create Server
     *
     * @param ServiceModel $serviceModel
     * @return bool
     * @throws NacosConfigNotFound
     */
    public function create(ServiceModel $serviceModel)
    {
        $serviceModel->validate();
        $resp = $this->request('POST', '/nacos/v1/ns/service', ['form_params' => $serviceModel->toCreateParams()]);
        return $this->assertResponse($resp, 'ok', "Nacos Client create service fail");
    }

    public function detail(string $serviceName, string $groupName = self::DEFAULT_GROUP, string $namespaceId = null)
    {
        if (empty($namespaceId)) {
            $namespaceId = $this->namespace;
        }
        $query = array_filter(compact(
            'serviceName',
            'groupName',
            'namespaceId',
        ));

        $resp = $this->request('GET', '/nacos/v1/ns/service', ['query' => $query]);
        $data = json_decode((string)$resp->getBody(), true);
        $data['serviceName'] = $data['name'];

        return new ServiceModel($data);
    }

}
