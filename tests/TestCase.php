<?php

namespace Nacos\Tests;

use Nacos\Client;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected $client;

    /**
     * This method is called before each test.
     */
    protected function setUp():void/* The :void return type declaration that should be here would cause a BC issue */
    {
        $client = new Client('172.17.0.115', 8848);
    }

}
