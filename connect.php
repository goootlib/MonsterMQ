<?php

spl_autoload_register('loadMonsterClass');

header('Content-Type:text/html,charset=utf-8');

try {
    echo '<pre>';

    $producer = new MonsterMQ\Client\Producer();
    $producer->logIn();

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
	$consumer->suspendChannel(1);
    */
}catch (\Exception $e) {
    echo $e->getMessage();
}

function loadMonsterClass($className){
    $className = str_replace('\\',DIRECTORY_SEPARATOR, $className);
    require_once dirname(__DIR__).DIRECTORY_SEPARATOR.$className.'.php';
}