<?php

require __DIR__."/vendor/autoload.php";

spl_autoload_register('loadMonsterClass');

header('Content-Type:text/html,charset=utf-8');

try {
    echo '<pre>';
    $socket = new MonsterMQ\Connections\Stream();
    $socket->connect();
    $transmitter = new MonsterMQ\Connections\BinaryTransmitter($socket);
	$dispatcher = new MonsterMQ\AMQPDispatchers\ConnectionDispatcher($transmitter);
	$session = new MonsterMQ\Core\Session($dispatcher);
	$session->logIn();


    echo '</pre>';
    /*
	$producer->session()->locale('en_US')->virtualHost('/')->logIn('guest','guest');
    $producer->newDirectExchange('name')->setPersistent()->declare();
    $producer->newFanoutExchange('another name')->setPersistent()->declare();
    $producer->newTopicExchange('yet another name')->setPersistent()->declare();
    $producer->newQueue('queue name')->declare()->bind('binding name');
	$producer->defaultRoutingKey('routing key');
    $producer->overrideDefaultExchange('exchange name');
	$producer->changeChannel(1);
	$consumer->useMultipleChannels([1,2]);
    */
}catch (\Exception $e) {
    echo $e->getMessage();
}

function loadMonsterClass($className){
    $className = str_replace('\\',DIRECTORY_SEPARATOR, $className);
    require_once dirname(__DIR__).DIRECTORY_SEPARATOR.$className.'.php';
}