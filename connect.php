<?php

require __DIR__."/vendor/autoload.php";

spl_autoload_register('loadMonsterClass');

header('Content-Type:text/html,charset=utf-8');

try {
    echo '<pre>';
    $socket = new MonsterMQ\Connections\Stream();
    $socket->connect();
    $transmitter = new MonsterMQ\Connections\BinaryTransmitter($socket);
	$connection = new MonsterMQ\AMQPDispatchers\ConnectionDispatcher($transmitter);

	$connection->receive_start();

	//Принять аргументы из receive_start
	$connection->send_start_ok('AMQPLAIN', 'guest','guest','en_US');

    $tuningParameters = $connection->receive_tune();

	$connection->send_tune_ok(
	    $tuningParameters['channelMax'],
        $tuningParameters['frameMax'],
        $tuningParameters['heartbeat']
    );

	$connection->send_open();
    echo '</pre>';
    /*
    $producer->newDirectExange('name')->setPersistent()->declare();
    $producer->newFanoutExange('another name')->setPersistent()->declare();
    $producer->newTopicExange('yet another name')->setPersistent()->declare();
    $producer->newQueue('queue name')->declare()->bind('binding name');
    */
}catch (\Exception $e) {
    echo $e->getMessage();
}

function loadMonsterClass($className){
    $className = str_replace('\\',DIRECTORY_SEPARATOR, $className);
    require_once dirname(__DIR__).DIRECTORY_SEPARATOR.$className.'.php';
}