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

    $producer->queue('queue-1')->declare()->bind('my_direct', 'cba');
    $producer->queue('queue-2')->setDurable()->declare()->bind('my_topic','abc');
    $producer->queue('queue-3')->setAutodelete()->declare()->bind('my_direct', 'cab');
    $producer->queue('queue-4')->setExclusive()->declare()->bind('my_direct', 'bca');
    $producer->queue('queue-5')->setDurable()->declare()->bind('my_direct', 'bac');
    $producer->queue('queue-1')->unbind('my_direct', 'abc');

    $producer->queue('queue-1')->deleteIfUnused();
    $producer->queue('queue-2')->deleteIfEmpty();
    $producer->queue('queue-3')->delete();
    $producer->queue('queue-4')->purge();

    $producer->qos()->prefetchCount(10)->perConsumer()->apply();
    $producer->publish('message-1', 'bac', 'my_direct');
    $producer->overrideDefaultExchange('my_direct');
    $producer->defaultRoutingKey('bac');
    $producer->publish('message-2');
    $producer->disconnect();

    /**
    $consumer = new \MonsterMQ\Client\Consumer();
    $consumer->logIn();
    $consumer->noAck()->startConsume('queue-5');
    $consumer->stopConsume();

    echo '</pre>';
    /*
	$producer->events()->channelSuspension(function($channel) use ($producer){
		//handle channel suspension
	})->channelClosure(function($channel) use ($producer){
		//handle channel closure
	});

	$producer->defaultRoutingKey('routing key');
    $producer->overrideDefaultExchange('exchange name');
	
	$producer->changeChannel(1);
	$producer->currentChannel();
    */
}catch (\Exception $e) {
    echo '</pre>';
    echo $e->getMessage()."</br>";
    echo $e->getFile()."</br>";
    echo $e->getLine()."</br>";
    var_dump($e->getTrace());
    echo '</pre>';

}

function loadMonsterClass($className){
    $className = str_replace('\\',DIRECTORY_SEPARATOR, $className);
    require_once dirname(__DIR__).DIRECTORY_SEPARATOR.$className.'.php';
}