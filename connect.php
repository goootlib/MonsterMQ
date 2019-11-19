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

	$connection->send_start_ok();

	$connection->receive_tune();
    echo '</pre>';
}catch (\Exception $e) {
    echo $e->getMessage();
}

function loadMonsterClass($className){
    $className = str_replace('\\',DIRECTORY_SEPARATOR, $className);
    require_once dirname(__DIR__).DIRECTORY_SEPARATOR.$className.'.php';
}