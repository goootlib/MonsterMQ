<?php

require __DIR__ . "/Interfaces/TableValuePacker.php";
require __DIR__ . "/Interfaces/TableValueUnpacker.php";
include __DIR__ . '/Interfaces/Stream.php';
include __DIR__ . '/Connections/Stream.php';
include __DIR__ . '/Exceptions/NetworkException.php';
require __DIR__ . '/Interfaces/BinaryTransmitter.php';
require __DIR__ . "/Connections/TableValueUnpacker.php";
include __DIR__ . '/Connections/BinaryTransmitter.php';
include __DIR__ . '/Support/FieldType.php';
require __DIR__ . "/Support/FrameDelimiting.php";
require __DIR__ . "/Interfaces/AMQPClass.php";
require __DIR__ . "/Interfaces/AMQPDispatchers/ConnectionDispatcher.php";
require __DIR__ . "/AMQPDispatchers/ConnectionDispatcher.php";
require __DIR__ . "/Connections/TableValuePacker.php";

require __DIR__."/vendor/autoload.php";


header('Content-Type:text/html,charset=utf-8');

try {
    echo '<pre>';
    $socket = new MonsterMQ\Connections\Stream();
    $socket->connect();
    $transmitter = new MonsterMQ\Connections\BinaryTransmitter($socket);
	$connection = new MonsterMQ\AMQPDispatchers\ConnectionDispatcher($transmitter);

	$connection->receive_start();
	/*
	$dataArray = [
        'product' => array('S', 'MonsterMQ'),
        'platform' => array('S', 'PHP'),
        'version' => array('S', '0.1.0')

    ];
    $binaryData = "";
    foreach ($dataArray as $key => $valueWithType) {
        $binaryData .= $transmitter->tableValuePacker->packFieldTableValue('s', $key);
        $binaryData .= $valueWithType[0];
        $binaryData .= $transmitter->tableValuePacker->packFieldTableValue($valueWithType[0], $valueWithType[1]);
    }
    $data = "";
    $data = pack('n', 10);
    $data .= pack('n', 11);
    $tablelength = strlen($binaryData);
    $tablelength = $transmitter->tableValuePacker->packFieldTableValue("i", $tablelength);
    $data .= $tablelength;
    $data .= $binaryData;

    $data .= $transmitter->tableValuePacker->packFieldTableValue('s', 'AMQPLAIN');

    $t = new \MonsterMQ\Connections\BinaryTransmitter($socket);
    $t->enableBuffering();
    $t->sendFieldTable(['LOGIN' => ['S','guest'],'PASSWORD' => ['S','guest']]);
    $response = substr($t->getBufferContent(),4);
    $data .= $transmitter->tableValuePacker->packFieldTableValue('S',  $response);

    $data .= $transmitter->tableValuePacker->packFieldTableValue('s','en_US');



    $transmitter->sendLong(strlen($data));
    $result = $transmitter->sendRaw($data);
    $transmitter->sendOctet(0xCE);
    */

	$connection->send_start_ok();



	$writer = new \PhpAmqpLib\Wire\AMQPWriter();
    $dataArray = [
        'product' => array('S', 'MonsterMQ'),
        'platform' => array('S', 'PHP'),
        'version' => array('S', '0.1.0'),
        'copyright' => array('S', '')

    ];
	$writer->write_table($dataArray);
	$writer->write_shortstr('AMQPLAIN');
    $w = new \PhpAmqpLib\Wire\AMQPWriter();
    $w->write_table(['LOGIN' => ['S','guest'],'PASSWORD' => ['S','guest']]);
    $cred = substr($w->getvalue(),4);
    var_dump($cred);
    $writer->write_longstr($cred);
    $writer->write_shortstr('en_US');
	$value = $writer->getvalue();
	//var_dump($data);
	var_dump($value);
	//var_dump($data === $value);


	/*
	$transmitter->sendLong(strlen($value) + 4);
	$transmitter->sendShort(10);
	$transmitter->sendShort(11);
	$transmitter->sendRaw($value);
    $transmitter->sendOctet(0xCE);
    */

	//$connection->receive_secure();
    echo '</pre>';
}catch (\Exception $e){
    echo $e->getMessage();
}