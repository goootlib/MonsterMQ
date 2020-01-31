# MonsterMQ
MonsterMQ is a PHP implementation of AMQP client. It provides consumer and producer AMQP client variations with convenient and elegant API.
If you are already familiar with AMQP and RabbitMQ read on this manual, otherwise I would recommend to get started with reading this tutorial.
## Requirements
MonsterMQ intented for usage of 0.9.1 version of AMQP protocol, therefore it supports all RabbitMQ versions which support AMQP 0.9.1.
Also MonsterMQ requires PHP 7.1 or higher.
## Setup
To install the library use the following composer command
```
composer require monstermq/monstermq
```
Then include composer autoloader in your script files to gain access to the library classes
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
Further we will examine all features provided by them.
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

To enable usage of self-signed certificates use **allowSelfSigned()** method of network module
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
**changeChannel($channel)** method accepts one optional argument which is a channel number that going to be used. If you omit the argument this method will choose the channel number automatically and return its value for you. If specified channel suspended by the server **changeChannel($channel)** will return **false**.
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
 Use **declare()** method with **newDirectExchange($exchangeName)** to declare new direct exchange, with **newFanoutExchange($exchangeName)** to declare new fanout exchange or with **newTopicExchange($exchangeName)** to declare new topic exchange on consumer or producer instance. You also may set exchanges as *durable* or *autodelete*. Durable exchanges remain active when a server restarts. Non-durable exchanges (transient exchanges) are purged when a server restarts. Autodelete exchanges delete if no queues using them remain.
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
