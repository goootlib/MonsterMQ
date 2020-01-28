# MonsterMQ
MonsterMQ is a php implementation of AMQP client. It provides consumer and producer AMQP client variations with convenient and elegant API.
If you are already familiar with AMQP and RabbitMQ read on, otherwise I advise to get started with reading this tutorial.
## Supported RabbitMQ versions
MonsterMQ intented for usage of 0.9.1 version of AMQP protocol therefore it supports all AMQP versions which support AMQP 0.9.1.
## Setup
To install MonsterMQ use following composer command
```
composer require monstermq/monstermq
```
Then include composer autoloader in your script files to gain the access to library files
```
require_once __DIR__.'/vendor/autoload.php';
```
## Usage
The library provides two classes to work with.
The producer:
```
$producer = new \MonsterMQ\Client\Producer();
```
And the consumer:
```
$consumer = new \MonsterMQ\Client\Consumer();
```
Further we examine all features provided by them.
### Common features
Both client variations provide following features:
- Network connection establishment with TLS or TCP.
- Session establishment with username and password.
- Exchange declarations.
- Queue declarations.
- Events management.
#### Network connection establishment
In order to connect to specified RabbitMQ server by TCP protocol first create producer or consumer instance and then call connect method with ip address and port number of your RabbitMQ server.
