<?php

namespace Nacos\Tests;

use Nacos\Exceptions\NacosNamingNoAliveInstance;
use Nacos\Exceptions\NacosNamingNotFound;
use Nacos\Models\BeatInfo;
use Nacos\Models\BeatResult;
use Nacos\Models\ServiceInstance;
use Nacos\Client;
use Nacos\Instance;
use Nacos\Models\ServiceInstanceList;
use PHPUnit\Framework\AssertionFailedError;

class InstanceTest extends TestCase
{
    protected $instance;

    protected function setUp():void/* The :void return type declaration that should be here would cause a BC issue */
    {
        $this->instance = new Instance('172.17.0.115', 8848);
    }

    public function testServiceInstance()
    {
        $instance = new ServiceInstance();
        $instance->ip = '127.0.0.1';
        $instance->port = 7777;
        $instance->serviceName = 'hello.world';
        $instance->metadata = ['hello' => 'world'];

        $success = $this->instance->add($instance);
        self::assertTrue($success);

        $list = $this->instance->list('hello.world');
        self::assertInstanceOf(ServiceInstanceList::class, $list);
        self::assertInstanceOf(ServiceInstance::class, $list->hosts[0]);
        self::assertSame(['hello' => 'world'], $list->hosts[0]->metadata);

        $instance->metadata = ['test' => 'nacos'];
        $updateSuccess = $this->instance->update($instance);
        self::assertTrue($updateSuccess);

        $listAfterUpdate = $this->instance->list('hello.world');
        self::assertSame(['test' => 'nacos'], $listAfterUpdate->hosts[0]->metadata);

        $result = $this->instance->get('hello.world', '127.0.0.1', 7777);
        self::assertInstanceOf(ServiceInstance::class, $result);

        $beat = new BeatInfo();
        $beat->ip = '127.0.0.1';
        $beat->port = 1234;
        $beatResp = $this->instance->beat('hello.world', $beat);
        self::assertInstanceOf(BeatResult::class, $beatResp);
    }

    public function testSelectOneHealthyInstance()
    {
        $instance = new ServiceInstance();
        $instance->serviceName = 'hello.world';
        $instance->ip = '127.0.0.1';
        $instance->port = 7777;
        $instance->healthy = true;
        $success = $this->instance->add($instance);
        self::assertTrue($success);

        $beat = new BeatInfo();
        $beat->ip = $instance->ip;
        $beat->serviceName = $instance->serviceName;
        $beat->port = $instance->port;
        $this->instance->beat('hello.world', $beat);

        sleep(1);

        $instance = $this->instance->select('hello.world');
        self::assertInstanceOf(ServiceInstance::class, $instance);
        self::assertSame('hello.world', $instance->serviceName);

        try {
            $this->instance->select('hello.world.not-exists');
            self::assertTrue(false, 'Failed to throw an exception, this line should not be executed');
        } catch (AssertionFailedError $e) {
            throw $e;
        } catch (\Throwable $e) {
            self::assertInstanceOf(NacosNamingNotFound::class, $e);
        }

        // test NacosNamingNoAliveInstance exception
        $instance->serviceName = 'hello.another-world';
        $success = $this->instance->add($instance);
        self::assertTrue($success);
        $success = $this->instance->delete($instance->serviceName, $instance->ip, $instance->port);
        self::assertTrue($success);
        try {
            $this->instance->select('hello.another-world');
            self::assertTrue(false, 'Failed to throw an exception, this line should not be executed');
        } catch (AssertionFailedError $e) {
            throw $e;
        } catch (\Throwable $e) {
            self::assertInstanceOf(NacosNamingNoAliveInstance::class, $e);
        }
    }
}
