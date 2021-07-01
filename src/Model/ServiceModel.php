<?php

declare(strict_types=1);

namespace Nacos\Model;

class ServiceModel extends AbstractModel
{
    /**
     * @var string
     */
    public $serviceName;

    /**
     * @var string
     */
    public $groupName;

    /**
     * @var string
     */
    public $namespaceId;

    /**
     * Between 0 to 1.
     * @var float
     */
    public $protectThreshold = 0.0;

    /**
     * @var string
     */
    public $metadata;

    /**
     * A JSON string.
     *
     * @var string
     */
    public $selector;

    /**
     * @var string[]
     */
    public $requiredFields = ['serviceName'];
}
