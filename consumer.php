<?php

spl_autoload_register('loadMonsterClass');

$consumer = new \MonsterMQ\Client\Consumer();
$consumer->logIn();
$consumer->consume('queue-5');
$consumer->wait(function ($message, $channel) use ($consumer){
   echo $message."\n";
   echo $channel."\n";
   $consumer->ackLast();
});

function loadMonsterClass($className){
    $className = str_replace('\\',DIRECTORY_SEPARATOR, $className);
    require_once dirname(__DIR__).DIRECTORY_SEPARATOR.$className.'.php';
}