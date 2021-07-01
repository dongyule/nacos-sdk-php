<?php

declare(strict_types=1);

namespace Nacos\Model;

use Nacos\Utils\Codec\Xml;

class ConfigModel extends AbstractModel
{
    /**
     * @var string
     */
    public $tenant;

    /**
     * @var string
     */
    public $dataId;

    /**
     * @var string
     */
    public $group;

    /**
     * @var string
     */
    public $content;

    /**
     * @var string
     */
    public $type = 'json';

    /**
     * @var string[]
     */
    public $requiredFields = [
        'dataId',
    ];

    public function parse($originConfig)
    {
        switch ($this->type) {
            case 'json':
                return is_array($originConfig) ? $originConfig : json_decode($originConfig, true);
            case 'yml':
            case 'yaml':
                return is_array($originConfig) ? $originConfig : yaml_parse($originConfig);
            case 'xml':
                return Xml::toArray($originConfig);
            default:
                return $originConfig;
        }
    }
}
