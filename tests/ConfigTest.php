<?php

namespace Nacos\Tests;

use Nacos\Config;
use Nacos\Model\ConfigModel;
use PHPUnit\Framework\AssertionFailedError;

class ConfigTest extends TestCase
{
    protected $config;

    protected function setUp():void/* The :void return type declaration that should be here would cause a BC issue */
    {
        $this->config = new Config([
            'host'=>'172.17.0.115',
            'port' => 8848
        ]);
    }

    public function testConfig()
    {
        $configModel = new ConfigModel([
            'tenant' => 'public',
            'data_id' => 'test.json',
            'group' => 'DEFAULT_GROUP',
            'content' => '{"name":"v1"}',
        ]);
        $publishResult = $this->config->set($configModel);
        self::assertTrue($publishResult);

        $newValue = $this->config->get($configModel);
        self::assertSame('{"name":"v1"}', $newValue);

        $removeResult = $this->config->delete($configModel);
        self::assertTrue($removeResult);
    }

    public function testConfigNotFound()
    {
        $dataId = 'not-exists-data-id.' . microtime(true);
        $configModel = new ConfigModel([
            'tenant' => 'public',
            'data_id' => $dataId,
            'group' => 'DEFAULT_GROUP',
        ]);
        try {
            $this->config->get($configModel);
            self::assertEmpty(false, 'Failed to throw an exception, this line should not be executed');
        } catch (AssertionFailedError $e) {
            throw $e;
        } catch (\Throwable $e) {
            //self::assertInstanceOf(NacosConfigNotFound::class, $e);
        }
    }

    public function testListenConfig()
    {
        $dataId = 'nginx.conf';
        $group = 'DEFAULT_GROUP';

        $client = new Config('172.17.0.115', 8848);
        $client->setTimeout(1);
        $content = $client->get($dataId, $group);
        $contentMd5 = md5($content);
        $pid = pcntl_fork();
        if ($pid === 0) {
            // fork child process
            sleep(2);
            $success = $client->set($dataId, $group, 'world=hello' . microtime());
            self::assertTrue($success);
            exit; // child process exit
        }

        $cache = new ConfigModel();
        $cache->dataId = $dataId;
        $cache->group = $group;
        $cache->contentMd5 = $contentMd5;
        $result = $client->listen([$cache]);
        self::assertTrue(is_array($result));
        self::assertSame($dataId, $result[0]->dataId);
        self::assertSame($group, $result[0]->group);
    }

    public function testGetParsedConfigs()
    {
        $content = "hello=world\nabc=efg";
        $success = $this->config->set('config.properties', 'group_name', $content);
        self::assertTrue($success);

        sleep(1);

        $expected = ['hello' => 'world', 'abc' => 'efg'];

        $res = $this->config->getParsedConfigs('config.properties', 'group_name');
        self::assertSame($expected, $res);

        $res = $this->config->getParsedConfigs('config.properties', 'group_name', false);
        self::assertSame($expected, $res);
    }

}
