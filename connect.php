<?php

include __DIR__ . '/Interfaces/Socket.php';
include __DIR__ . '/Connections/Socket/SocketIO.php';
include __DIR__.'/Exceptions/SocketException.php';
include __DIR__.'/Connections/BinaryTransmitter.php';


header('Content-Type:text/html,charset=utf-8');

try {
    $transmitter = new MonsterMQ\Connections\BinaryTransmitter($socket = new \MonsterMQ\Connections\IO\SocketIO());
	$socket->writeRaw("AMQP\x0\x0\x9\x1");
    $frameType = $transmitter->receiveOctet();
    $chanel = $transmitter->receiveShort();
    $size = $transmitter->receiveLong();
    var_dump($frameType,$chanel,$size);
    $classid = $transmitter->receiveShort();
    $methodid = $transmitter->receiveShort();
    $major = $transmitter->receiveOctet();
    $minor = $transmitter->receiveOctet();
    $tablesize = $transmitter->receiveLong();
    $key = $transmitter->receiveShortStr();
    echo 'payload'.PHP_EOL;
    var_dump($key);
}catch (\Exception $e){
    echo $e->getMessage();
}