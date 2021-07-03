<?php

namespace Nacos\Tests;

use Nacos\Config;
use Nacos\Model\ConfigModel;
use PHPUnit\Framework\AssertionFailedError;
use Nacos\Exception\RequestException;

class ConfigTest extends \PHPUnit\Framework\TestCase
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
            'data_id' => 'test.json',
            'group' => 'DEFAULT_GROUP',
            'content' => '{"name":"v1"}',
        ]);
        $publishResult = $this->config->set($configModel);
        self::assertTrue($publishResult);

        sleep(1);
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
            self::assertTrue(false, 'Failed to throw an exception, this line should not be executed');
        } catch (\Throwable $e) {
            self::assertInstanceOf(RequestException::class, $e);
        }
    }

    public function testListenConfig()
    {
        $configModel = new ConfigModel([
            'dataId' => 'nginx.conf',
            'group' => 'DEFAULT_GROUP'
        ]);
        $content = $this->config->get($configModel);
        $configModel->content = $content;
        $pid = pcntl_fork();
        if ($pid === 0) {
            // fork child process
            sleep(2);
            $configModel = new ConfigModel([
                'dataId' => 'nginx.conf',
                'group' => 'DEFAULT_GROUP',
                'content' => 'world=hello' . microtime(),
            ]);
            $success = $this->config->set($configModel);
            self::assertTrue($success);
            exit; // child process exit
        }

        $result = $this->config->listen([$configModel]);
        self::assertTrue(is_array($result));
        self::assertSame('nginx.conf', $result[0]->dataId);
        self::assertSame('DEFAULT_GROUP', $result[0]->group);
    }

    public function testGetParsedConfigs()
    {
        $content = "hello=world\nabc=efg";
        $configModel = new ConfigModel([
            'dataId' => 'nginx.conf',
            'group' => 'DEFAULT_GROUP',
            'content'=> $content,
            'type' => 'properties'
        ]);
        $success = $this->config->set($configModel);
        self::assertTrue($success);

        sleep(1);

        $expected = ['hello' => 'world', 'abc' => 'efg'];

        $res = $this->config->get($configModel, true);
        self::assertSame($expected, $res);
    }

}
