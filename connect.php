<?php

include __DIR__ . '/Interfaces/Stream.php';
include __DIR__ . '/Connections/Stream.php';
include __DIR__ . '/Exceptions/NetworkException.php';
require __DIR__ . '/Interfaces/BinaryTransmitter.php';
require __DIR__ . "/Connections/FieldTableParser.php";
include __DIR__ . '/Connections/BinaryTransmitter.php';
include __DIR__ . '/Support/FieldType.php';
require __DIR__ . "/Support/ValidateFrameDelimiter.php";
require __DIR__ . "/Interfaces/AMQPClass.php";
require __DIR__ . "/Interfaces/AMQPDispatchers/ConnectionDispatcher.php";
require __DIR__ . "/AMQPDispatchers/ConnectionDispatcher.php";


header('Content-Type:text/html,charset=utf-8');

try {
    echo '<pre>';
    $socket = new \MonsterMQ\Connections\Stream();
    $socket->connect();
    $transmitter = new MonsterMQ\Connections\BinaryTransmitter($socket);
	$connection = new \MonsterMQ\Classes\ConnectionDispatcher($transmitter);
	$connection->receive_start();
    echo '</pre>';
}catch (\Exception $e){
    echo $e->getMessage();
}