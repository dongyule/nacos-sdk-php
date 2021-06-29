# Nacos-Sdk-PHP

## Nacos-Sdk-PHP

Nacos-Sdk-PHP for PHP client allows you to access Nacos OpenAPI. [Open API Guide](https://nacos.io/en-us/docs/open-api.html)

## Requirements

- PHP ^7.0
## Installation

```powershell
composer require hcz/nacos-sdk-php
```
## Getting Started

```php
use Nacos\Config;

$client = new Config('localhost', 8848);

$dataId = 'database.php';
$group = 'DEFAULT_GROUP';
$result = $client->get($dataId, $group);
```

## Use Namespace

```php
use Nacos\Config;

$dataId = 'database.php';
$group = 'DEFAULT_GROUP';
$namespace = 'c78ce19d-82d1-456a-8552-9a0db6c11d01';

$client = new Config('localhost', 8848);
$client->setNamespace($namespace);
$result = $client->get($dataId, $group);
```
## Listener Config

```php
use Nacos\Config;
use Nacos\Models\Config as ConfigModel;

$dataId = 'database.php';
$group = 'DEFAULT_GROUP';
$namespace = 'c78ce19d-82d1-456a-8552-9a0db6c11d01';

$client = new Config('localhost', 8848);
$client->setNamespace($namespace);
$client->setTimeout(3);
$content = $client->get($dataId, $group);
$contentMd5 = md5($content);

$cache = new ConfigModel();
$cache->dataId = $dataId;
$cache->group = $group;
$cache->namespace = $namespace;
$cache->contentMd5 = $contentMd5;
$result = $client->listen([$cache]);
if(!empty($result)) {
    $updateContent = $client->get($dataId, $group);
    echo '[x] update content : ' . $updateContent, "\n";
} else {
    echo '[x] this is not update ', "\n";
}
```

## Register an instance to the service

```php
use Nacos\Instance;
use Nacos\Models\ServiceInstance;

$client = new Instance('localhost', 8848);

$serviceName  = 'NacosTaskService';
$instance = new ServiceInstance();
$instance->serviceName = $serviceName;
$instance->ip = '127.0.0.1';
$instance->port = 80;
$instance->healthy = true;
$instance->ephemeral = false;

$isSuccess = $client->register($instance);
if(true === $isSuccess) {
    echo '[x] create service instance success ', "\n";
} else {
    echo '[x] create service instance fail ', "\n";
}
```

## API
### Request Options

- setNamespace
  - string $namespace
- setTimeout
  - int $timeout
### Config API

- get
  - string $dataId
  - string $group = Client::DEFAULT_GROUP
- set
  - string $dataId
  - string $group
  - $content
- delete
  - string $dataId
  - string $group = Client::DEFAULT_GROUP
- listen
  - array $configs
  - int $timeout = 30
### Instance API

- register
  - ServiceInstance $instance
- delete
  - string $serviceName
  - string $ip
  - int $port
  - string $clusterName = null
  - string $namespaceId = null
- update
  - ServiceInstance $instance
- list
  - string $serviceName
  - string $namespaceId = null
  - array $clusters = []
  - bool $healthyOnly = false
- get
  - string $serviceName
  - string $ip
  - int $port
  - string $namespaceId = null
  - string $cluster = null
  - bool $healthyOnly = false
- beat
  - string $serviceName
  - BeatInfo $beat
- call
  - string $serviceName
  - string $method
  - string $uri
  - array $options = []
  - string $namespaceId = null
  
## PHPUnit Test

ClientTest
```
./vendor/bin/phpunit --bootstrap src/Nacos/Client.php tests/ClientTest.php
```
- phpunit 调用命令行测试PHPUnit
- --bootstrap src/Client.php 指示PHPUnit命令行测试在测试之前执行　include src/Client.php
- tests/ClientTest.php 指示PHPUnit命令行测试要执行的测试 ClientTest 类声明在 tests/ClientTest.php
- http://www.phpunit.cn/getting-started.html

ConfigTest
```
./vendor/bin/phpunit --bootstrap src/Config.php tests/ConfigTest.php
```

InstanceTest
```
./vendor/bin/phpunit --bootstrap src/Instance.php tests/InstanceTest.php
```
## Other


Git Tag
```
git push origin v0.0.42
// or push all
git push origin --tags
```