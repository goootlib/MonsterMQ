<?php

include __DIR__ . '/Interfaces/Stream.php';
include __DIR__ . '/Connections/Stream.php';
include __DIR__ . '/Exceptions/NetworkException.php';
require __DIR__ . "/Connections/FieldTableParser.php";
include __DIR__.'/Connections/BinaryTransmitter.php';
include __DIR__.'/Support/FieldType.php';


header('Content-Type:text/html,charset=utf-8');

try {
    echo '<pre>';
    $socket = new \MonsterMQ\Connections\Stream();
    $socket->connect();
    $transmitter = new MonsterMQ\Connections\BinaryTransmitter($socket);
	$socket->writeRaw("AMQP\x0\x0\x9\x1");
    $frameType = $transmitter->receiveOctet();
    $chanel = $transmitter->receiveShort();
    $size = $transmitter->receiveLong();
    var_dump($frameType,$chanel,$size);
    $classid = $transmitter->receiveShort();
    $methodid = $transmitter->receiveShort();
    $major = $transmitter->receiveOctet();
    $minor = $transmitter->receiveOctet();
    $table = $transmitter->receiveFieldTable();
    var_dump($table);
    $mechanism = $transmitter->receiveLongStr();
    $locales = $transmitter->receiveLongStr();
    var_dump($mechanism, $locales);
    echo '</pre>';
}catch (\Exception $e){
    echo $e->getMessage();
}