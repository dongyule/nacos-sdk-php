<?php

namespace Nacos\Tests;

use Nacos\Exceptions\NacosNamingNoAliveInstance;
use Nacos\Exceptions\NacosNamingNotFound;

use Nacos\Model\InstanceModel;
use Nacos\Model\ServiceModel;
use Nacos\Client;
use Nacos\Instance;
use Nacos\Models\ServiceInstanceList;
use PHPUnit\Framework\AssertionFailedError;

class InstanceTest extends \PHPUnit\Framework\TestCase
{
    protected $instance;

    protected function setUp():void/* The :void return type declaration that should be here would cause a BC issue */
    {
        $this->instance = new Instance([
            'host' => '172.17.0.115',
            'port' => 8848
        ]);
    }

    public function testInstance()
    {
        $service = new ServiceModel([
            'serviceName' => 'hello.world',
        ]);

        $instance = new InstanceModel();
        $instance->ip = '127.0.0.1';
        $instance->port = 7777;
        $instance->serviceName = 'hello.world';
        $instance->metadata = ['hello' => 'world'];

        $success = $this->instance->register($instance);
        self::assertTrue($success);

        $list = $this->instance->list($service);
        self::assertSame(['hello' => 'world'], $list['hosts'][0]['metadata']);

        $instance->metadata = ['test' => 'nacos'];
        $updateSuccess = $this->instance->update($instance);
        self::assertTrue($updateSuccess);

        $listAfterUpdate = $this->instance->list($service);
        self::assertSame(['test' => 'nacos'], $listAfterUpdate['hosts'][0]['metadata']);

        //$result = $this->instance->detail($instance);
        //$beatResp = $this->instance->beat($service, $instance);
    }

    public function testSelectOneHealthyInstance()
    {
        $service = new ServiceModel([
            'serviceName' => 'gd-share-engine',
            'namespaceId' => 'dev'
        ]);

        $instance = new InstanceModel();
        $instance->ip = '127.0.0.1';
        $instance->port = 7777;
        $instance->serviceName = 'hello.world';
        $instance->metadata = ['hello' => 'world'];

        sleep(1);

        $instance = $this->instance->getOptimal($service);
        self::assertInstanceOf(InstanceModel::class, $instance);
        self::assertSame('gd-share-engine', $instance->serviceName);

//        try {
//            $this->instance->getOptimal(new ServiceModel([
//                'serviceName' => 'hello.world.not_exists',
//            ]));
//            self::assertTrue(false, 'Failed to throw an exception, this line should not be executed');
//        } catch (AssertionFailedError $e) {
//            throw $e;
//        } catch (\Throwable $e) {
//            self::assertInstanceOf(NacosNamingNotFound::class, $e);
//        }
//
//        // test NacosNamingNoAliveInstance exception
//        $instance->serviceName = 'hello.another-world';
//        $success = $this->instance->add($instance);
//        self::assertTrue($success);
//        $success = $this->instance->delete($instance->serviceName, $instance->ip, $instance->port);
//        self::assertTrue($success);
//        try {
//            $this->instance->select('hello.another-world');
//            self::assertTrue(false, 'Failed to throw an exception, this line should not be executed');
//        } catch (AssertionFailedError $e) {
//            throw $e;
//        } catch (\Throwable $e) {
//            self::assertInstanceOf(NacosNamingNoAliveInstance::class, $e);
//        }
    }
}
