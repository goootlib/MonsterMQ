<?php

include __DIR__ . '/Interfaces/Socket.php';
include __DIR__ . '/Connections/Socket/SocketIO.php';
include __DIR__.'/Exceptions/SocketException.php';
include __DIR__.'/Connections/BinaryTransmitter.php';

include __DIR__.'/Support/FieldType.php';


header('Content-Type:text/html,charset=utf-8');

try {
    echo '<pre>';
    $transmitter = new MonsterMQ\Connections\BinaryTransmitter($socket = new \MonsterMQ\Connections\Socket\SocketIO());
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
    echo '</pre>';
}catch (\Exception $e){
    echo $e->getMessage();
}