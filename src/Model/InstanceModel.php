<?php

declare(strict_types=1);

namespace Nacos\Model;

class InstanceModel extends AbstractModel
{
    /**
     * @var null|string
     */
    public $serviceName;

    /**
     * @var null|string
     */
    public $groupName;

    /**
     * @var null|string
     */
    public $ip;

    /**
     * @var null|int
     */
    public $port;

    /**
     * @var null|string
     */
    public $clusterName;

    /**
     * @var null|string
     */
    public $namespaceId;

    /**
     * @var null|float|float|int
     */
    public $weight;

    /**
     * @var null|string
     */
    public $metadata;

    /**
     * @var null|bool
     */
    public $enabled;

    /**
     * @var null|bool
     */
    public $ephemeral;

    /**
     * @var null|bool
     */
    public $healthy;

    /**
     * @var null|string
     */
    public $instanceId;

    /**
     * @var string[]
     */
    public $requiredFields = [
        'ip',
        'port',
        'serviceName',
    ];

    public function getWeightDouble()
    {
        return $this->weight ? doubleval($this->weight) : 0;
    }

}
