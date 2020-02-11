# MonsterMQ
MonsterMQ is a PHP implementation of AMQP client. It provides consumer and producer AMQP client variations with convenient and elegant API.
If you are already familiar with AMQP and RabbitMQ read on this manual, otherwise I would recommend to get started with reading this tutorial.
## Requirements
MonsterMQ intented for usage of 0.9.1 version of AMQP protocol, therefore it supports all RabbitMQ versions which support AMQP 0.9.1.
Also MonsterMQ requires PHP 7.1 or higher.
## Setup
To install the library use the following composer command
```
composer require goootlib/monster-mq:dev-master
```
Or you may add "goootlib/monster-mq":"dev-master" as a new dependency to the *require* section of your **composer.json** file like so:
```
{
    "require": {
        "other-vendor/other-package": "^5.4",
	"goootlib/monster-mq":"dev-master"
    }
}
```
And then call **composer update** command to install declared dependecies. Keep in mind - **composer update** will also update all your other dependecies to the latest versions.
After installation include composer autoloader in your script files to gain access to the library classes
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
Further we will examine all features provided by them. Also, when working with MonsterMQ on production environment don't forget to enclose all your code which utilizes MonsterMQ in try/catch construction and write code which handles exceptions instances that may be thrown during the work of the library.
### Common features
Both client variations provide following features:
- Network connection establishment with TLS or TCP.
- Session establishment with specified username and password.
- Exchange declarations.
- Queue declarations.
- Events management.
#### Network connection establishment
In order to connect to specified RabbitMQ server by TCP protocol first create producer or consumer instance and then call **connect()** method with ip address and port number of your RabbitMQ server. You may also specify connection timeout as a third argument of **connect()** method.
```
$consumer->connect('127.0.0.1', 5672, 10);
```
You may omit all arguments of **connect()** method. In this case MonsterMQ will try to connect to server on localhost with default port number (which is 5672).
#### Configuring network TCP connection
If you wish to cofigure your network connection you may use **network()** or **socket()** methods of consumer or producer instance. Both methods are the aliases to each other and provide access to the same programm module. Here are the methods they provide:
```
$consumer->socket()->bindTo(9999, '127.0.0.1')->enableNodelay()->disableKeepalive()
  ->setTimeout($seconds, $microseconds)->connect();
```
**bindTo($portNumber, $ipAddress)** - method binds MonsterMQ to specified port on specified network inteface, use this method if you wish to bind MonsterMQ to arbitary port number. Second argument (IP address) may be omitted, in this case IP address will be chosen automaticaly.

**enableNodelay()** - this method disables [Nagle's algorithm](https://en.wikipedia.org/wiki/Nagle%27s_algorithm). It is enabled by default.

**disableKeepalive()** - this method disables [keepalive TCP feature](https://en.wikipedia.org/wiki/Keepalive).

**setTimeout($seconds, $microseconds)** - this method sets reading/writing timeout for network connection. You may specify first and second arguments of the method as integers representing the number of seconds and microseconds accordingly, after which connection will be closed if no reading or writing to the socket have occured. setTimeout() method also allows to pass only one argument which might be an integer or a float, if this sigle argument is float the fractional part of it will be treated as microseconds whereas the number before the floating point will be treated as seconds.

#### Configuring encrypted network connection
MonsterMQ allows to use encrypted connections using TLS protocol. In order to utilize and configure it, call the following methods:
```
$consumer->network()->useTLS()->verifyPeer()->verifyPeerName()->peerName($name)
  ->CA($pathToCAFile)->certificate($pathToCertificateFile)
  ->privateKey($pathToPrivateKey)->password($password)
  ->verifyDepth($number)->ciphers($ciphers)->connect();
```
**useTLS()** - this method must be called to enable TLS.

**verifyPeer()** - this method must be called in order to enable peer verification. If you want only to use encryption without peer verification you may skip this method call.

**verifyPeerName()** - this method might be called in order to enable peer name verification, if you call this method you must also specify peer name by calling the peerName() method. Call to this method is not required if you don't want to verify name of remote peer certificate.

**peerName($name)** - specify peer name with this method when peer name verification enabled. Call to this method is not required if you don't want to verify name of remote peer certificate, or if you want that peer name will be automaticaly chosen based on the address argument of connect() method.

**CA($pathToCAFile)** - specify the path to Certificate Authority file with this method in order to be able to establish TLS connection.

**privateKey($pathToPrivateKey)** - specify the path to Private Key file with this method.

**password($password)** - specify the password with which your certificate was created. Call to this method is not required if your certficate was created without password.

**verifyDepth($number)** - specify the length of certificate chain after which verification will be aborted.

**ciphers($list)** - Sets list of ciphers to be used for connection. List of all system supported ciphers in format that this method accept may be obtained by 'openssl ciphers' cli command.

To enable utilizing of self-signed certificates use **allowSelfSigned()** method of network module:
```
$consumer->network()->useTLS()->allowSelfSigneed()
  ->CA($pathToCAFile)->certificate($pathToCertificateFile)
  ->privateKey($pathToPrivateKey)->password($password)
  ->connect();
```

**enableNodelay()** and **setTimeout()** may also be used for encrypted connections whereas keepalive feature is not available for TLS.

#### Session establishment

Use **logIn()** method on consumer or producer instance to start the session. This method accepts two arguments (which may be omitted), they are username and password of your RabbitMQ user. If you will omit login arguments, MonsterMQ will use 'guest' value for username and for password (which is credentials for default RabbitMQ user).
```
$consumer->connect('127.0.0.1', 5672);
$consumer->logIn('my-username', 'my-password');
```
Use the following methods of session module in oder to configure the session:
```
$consumer->session()->locale('en_US')->virtualHost('/')->logIn('my-username, 'my-password');
```
Previous methods allows you to choose locale and virtual host to be used.
Also should to mention, that if you want to connect to RabbitMQ running on default port (which is 5672) on localhost you may skip call of **connect()** method and call only **logIn()** method in oder to establish a TCP connection and a session.
#### Channels
To change the channel used call **changeChannel()** method of consumer or producer instance:
```
$consumer->changeChannel();
$consumer->changeChannel(2);
```
**changeChannel($channel)** method accepts one optional argument which is a channel number going to be used. If you omit the argument this method will choose the channel number automatically and return its value for you. If specified channel suspended by the server **changeChannel($channel)** will return **false**.
To close specified channel call **closeChannel($channel)** method with channel number to be closed as an argument. To get channel currently being used call **currentChannel()**.
#### Events
During the work of MonsterMQ and RabbitMQ last one can suspend or close overproducing channels. To handle this events use the following methods of events module:
```
$producer->events()->channelSuspesion(
 
 function ($suspendedChannel) use ($producer) {
    echo "channel {$suspendedChannel} was suspended";
    $producer->changeChannel();
  }
 
 )->channelClosure(
 
  function ($closedChannel) use ($producer) {
    echo "channel {$closedChannel} was closed";
    $producer->changeChannel();
   }
 
 );
 ```
 Closures which handle this events accept numbers of suspended or closed channels respectively.
 #### Exchanges
 Use **declare()** method with **newDirectExchange($exchangeName)** to declare new direct exchange, with **newFanoutExchange($exchangeName)** to declare new fanout exchange or with **newTopicExchange($exchangeName)** to declare new topic exchange on client (consumer or producer) instance. You also may set exchanges as *durable* or *autodelete*. Durable exchanges remain active when a server restarts. Non-durable exchanges (transient exchanges) are purged when a server restarts. Autodelete exchanges delete if no queues using them remain.
 ```
 $consumer->newDirectExchange('my-direct')->declare();
 $consumer->newFanoutExchange('my-fanout')->setAutodelete()->declare();
 $consumer->newTopicExchange('my-topic')->setDurable()->declare();
 ```
 If you wish to bind or unbind exchange to/from another exchange you may use the following methods:
 ```
 $consumer->exchange('exchange-to-be-bound')->bind('my-exchange', 'routing-key');
 $consumer->exchange('exchange-to-be-unbound')->unbind('my-exchange', 'routing-key');
 ```
 If you binding or unbinding exchanges from/to each other, don't forget to specify routing key as a second argument of **bind()** or **unbind()** methods.
 
#### Queues
In order to decalre queue first you need to specify queue name as first argument of **queue()** method of client (producer or consumer) instance and after that call the declare method. You may also set queues as *durable*, *autodelete* or *exclusive*. Durable queues remains after the server restart whereas non-durable (which used by default) are not. Autodelete queues delete if no consumers using it left. Exclusive queues may only be accessed by the current connection, and are deleted when that connection closes. 
```
$consumer->queue('queue-1')->declare()->bind('my_direct', 'cba');
$consumer->queue('queue-2')->setDurable()->declare()->bind('my_topic','abc');
$consumer->queue('queue-3')->setAutodelete()->declare()->bind('my_direct', 'cab');
$consumer->queue('queue-4')->setExclusive()->declare()->bind('my_direct', 'bca');
$consumer->queue('queue-5')->setDurable()->declare()->bind('my_direct', 'bac');
$consumer->queue('queue-1')->unbind('my_direct', 'abc');
```
**bind($exchange, $routingkey)** methods binds queue to specified exchange(first method argument) with specified routing key(second method argument).
**unbind($exchange, $routingKey)** method unbinds queue from exchange with routing key.
```
$consumer->queue('queue-1')->deleteIfUnused();
$consumer->queue('queue-2')->deleteIfEmpty();
$consumer->queue('queue-3')->delete();
$consumer->queue('queue-4')->purge();
```
Use **delete()**, **deleteIfEmpty()** and **deleteIfUnused()** method in oder to delete queue selected by **queue()** method. **deleteIfEmpty()** method deletes queue if it doesn't contains any messages. **deleteIfUnused()** deletes queue if no consumers using the queue left. And **delete()** method deletes queue in any case. Also you may use **purge()** method in order to remove all messages from a queue which are not awaiting acknowledgment. All four previous methods return number of messages had been deleted during deletion or purification.
### Producer
Use **publish($message, $routingKey, $exchange)** on producer instance to publish messages to a queue. Second argument of this method (which is routing key for publishing) may be omitted if you have already called **defaultRoutingKey($routingKey)** in order to set default routing key for all publications with no routing keys. As well as setting default routing key you may override default exchange to be used when the third argument of **publish()** method have omitted. To override the default RabbitMQ exchange use **overrideDefaultExchange($exchange)** method on producer instance.
```
$producer->publish('with exchange and routing key specified', 'abc', 'my_direct');
$producer->overrideDefaultExchange('my_direct');
$producer->defaultRoutingKey('abc');
$producer->publish('with overridden exchange and default routing key set');
$producer->publish('with overridden exchange and default routing key set 2');
```
Keep in mind that by default RabbitMQ provides default exchange which forwards messages to queues that named as the routing keys used upon publication. For example following messages will be delivered to queues with names 'queue-1' and 'queue-2' if you haven't already overrode default exchange with **overrideDefaultExchange()** method.
```
$producer->publish('with default RabbitMQ's exchange', 'queue-1');
$producer->publish('with default RabbitMQ's exchange 2', 'queue-2');
```
### Consumer
```
$consumer->consume('my-queue', true);
```
Use **consume($queue, $noAck)** method in order to start receiving messages from the queue. First argument of this method represents queue name to receive messages from. The second argument is optional and if it is omitted you will be have to acknowledge or reject  every received message with **ackLast()**, **ackAll()**, **rejectLast()**, **rejectAll()** methods. If this (second) argument of **consume()** method isn't set to *true*, messages will remain in queues until they get acklowledged. Use *$noAck* argument if you are not afraid of losing messages. **consume()** method also returns *consumerTag* which might be used with **stopConsume($consumerTag)** to stop consuming concrete queues. If **stopConsume()** method invoked without arguments, it will stop consuming all queues for channel currently being used.
#### Start consuming loop
Use **wait($closure)** to start consuming loop to asynchronously receive messages from the server. This method accepts only one argument wich is a closure to be called upon receipt of a message which in turn accepts two arguments: message and channel number have been used.
```
$consumer = new \MonsterMQ\Client\Consumer();
$consumer->logIn();
$consumer->queue('my-queue')->declare();
$consumer->consume('my-queue');
$consumer->wait(function ($message, $channel) use ($consumer){
   echo $message."\n";
   echo $channel."\n";
   $consumer->ackLast();
});
```
Use **ackLast()** to acknowledge last accepted message, **ackAll()** to acknowledge all unacknowledged messages up to the currently handled message (and including it). **rejectLast()**  method allows a client to reject last incoming message. It can be used to interrupt and cancel large incoming messages, or return untreatable messages to their original queue. **rejectAll()** rejects all unacknowledged messages up to the currently handled message (and including it).
#### Synchronous message obtaining
You may obtain messages syncronously without starting the consuming loop by using **get($queue, $noAck)** method. It accepts two arguments which is queue name from which you would like to obtain a message and whether to use acknowledgent for receiving message(true - means no acknowledgement). And it returns array first element of which is message and second is the channel number have been used.
```
[$message, $channel] = $consumer->get('my-queue', true);
echo $message;
echo $channel;
```
#### Quality of service
**prefetchCount($number)**  method in MonsterMQ allows you to send messages in advance, so that when the client finishes processing a message, the following message is already held locally, rather than needing to be sent down the channel. This setting can be used per channel or per consumer.
```
$producer->qos()->prefetchCount(10)->perConsumer()->apply();
$producer->qos()->prefetchCount(5)->perChannel()->apply();
```
#### Message redelivery
Use **redeliver($requeue)** method in order to redeliver all unacknowledged messages. It accepts only one optional argument which is when omitted will redeliver messages to the original recipient. And when set to *true* will attempt to requeue the message, potentially then delivering it to an alternative subscriber.
```
$consumer->wait(function ($message, $channel) use ($consumer){
  echo $message."\n";
  echo $channel."\n";
  $consumer->redeliver();
});
```
### Logging
If you will eximine Log directory of MonsterMQ you will find directories named accordingly to years containing log files of producers named accordingly to months when they had been written. Consumers do not write log files instead they output their procces description to cli output.
