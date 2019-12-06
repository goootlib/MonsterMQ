<?php

spl_autoload_register('loadMonsterClass');

header('Content-Type:text/html,charset=utf-8');

try {
    echo '<pre>';

    $producer = new MonsterMQ\Client\Producer();
    $producer->logIn();
    $producer->channel()->open(1);
    $producer->channel()->open(2);

    echo '</pre>';
    /*
	$producer->session()->locale('en_US')->virtualHost('/')->logIn('guest','guest');
	$producer->events()->channelSuspension(function($channel){})->channelClosure(function($channel){});
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
    echo '</pre>';
    echo $e->getMessage();
    echo $e->getFile();
    echo $e->getLine();
    var_dump($e->getTraceAsString());
    echo '</pre>';

}

function loadMonsterClass($className){
    $className = str_replace('\\',DIRECTORY_SEPARATOR, $className);
    require_once dirname(__DIR__).DIRECTORY_SEPARATOR.$className.'.php';
}