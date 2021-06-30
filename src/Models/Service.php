<?php

declare(strict_types=1);

namespace Nacos\Models;

use Nacos\Exceptions\NacosException;

class Service
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
    public $protectThreshold = 1.0;

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
     * __construct function
     *
     * @param array $info
     */
    public function __construct(array $info = [])
    {
        if (isset($info['metadata']) && is_string($info['metadata'])) {
            $metadata = json_decode($info['metadata'], true);
            if ($metadata) {
                $this->metadata = $metadata;
            }
            unset($info['metadata']);
        }

        foreach ($info as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function validate()
    {
        $this->assertNotNull('serviceName', $this->serviceName);
    }

    protected function assertNotNull($name, $value)
    {
        if (is_null($value)) {
            throw new NacosException("Service `{$name}` cannot be null");
        }
    }

    public function toCreateParams()
    {
        return $this->filter([
            'serviceName' => $this->serviceName,
            'groupName' => $this->groupName,
            'namespaceId' => $this->namespaceId,
            'protectThreshold' => $this->getProtectThresholdDouble(),
            'metadata' => $this->getMetadataJson(),
        ]);
    }

    /**
     * filter function
     *
     * @param array $array
     */
    protected function filter(array $array)
    {
        return array_filter($array, function ($value) {
            return !is_null($value);
        });
    }

    public function getProtectThresholdDouble()
    {
        return $this->protectThreshold ? doubleval($this->protectThreshold) : 0;
    }

    protected function getMetadataJson()
    {
        return $this->metadata ? json_encode($this->metadata) : null;
    }

}
