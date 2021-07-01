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

        return (string) $response->getBody() === 'ok';
    }

    public function delete(ConfigModel $configModel)
    {
        $response = $this->request('DELETE', '/nacos/v1/cs/configs', [
            RequestOptions::QUERY => $configModel->toArray(),
        ]);

        return (string) $response->getBody() === 'ok';
    }
}
