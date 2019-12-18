<?php

spl_autoload_register('loadMonsterClass');

header('Content-Type:text/html,charset=utf-8');

try {
    echo '<pre>';

    $producer = new MonsterMQ\Client\Producer();
    $producer->logIn();
    $producer->newDirectExchange('my_direct')->declare();
    $producer->newFanoutExchange('my_fanout')->setAutodelete()->declare();
    $producer->newTopicExchange('my_topic')->setDurable()->declare();
    $producer->exchange('my_direct')->bind('my_topic', 'abc');
    $producer->exchange('my_direct')->unbind('my_topic', 'abc');
    $producer->exchange('my_direct')->delete();

    echo '</pre>';
    /*
	$producer->session()->locale('en_US')->virtualHost('/')->logIn('guest','guest');
	
	$producer->events()->channelSuspension(function($channel) use ($producer){
		//handle flow suspension
	})->channelClosure(function($channel) use ($producer){
		//handle channel closure
	})->anotherChannelIncoming(function($channel) use ($producer){
		//handle incoming on channel that is not currently selected
	});
	
    $producer->newDirectExchange('name')->setPersistent()->declare();
    $producer->newFanoutExchange('another name')->setPersistent()->declare();
    $producer->newTopicExchange('yet another name')->setPersistent()->declare();
	
    $producer->newQueue('queue name')->declare()->bind('binding name');
	
	$producer->defaultRoutingKey('routing key');
    $producer->overrideDefaultExchange('exchange name');
	
	$producer->changeChannel(1);
	$producer->currentChannel();
	$consumer->useMultipleChannels([1,2]);
    */
}catch (\Exception $e) {
    echo '</pre>';
    echo $e->getMessage();
    echo $e->getFile();
    echo $e->getLine();
    var_dump($e->getTrace());
    echo '</pre>';

}

function loadMonsterClass($className){
    $className = str_replace('\\',DIRECTORY_SEPARATOR, $className);
    require_once dirname(__DIR__).DIRECTORY_SEPARATOR.$className.'.php';
}