<?php

namespace MonsterMQ\Client;

use MonsterMQ\AMQPDispatchers\ChannelDispatcher;
use MonsterMQ\AMQPDispatchers\ConnectionDispatcher;
use MonsterMQ\AMQPDispatchers\ExchangeDispatcher;
use MonsterMQ\AMQPDispatchers\QueueDispatcher;
use MonsterMQ\Connections\BinaryTransmitter;
use MonsterMQ\Connections\Stream;
use MonsterMQ\Core\Channel;
use MonsterMQ\Core\Exchange;
use MonsterMQ\Core\Qos;
use MonsterMQ\Core\Session;
use MonsterMQ\Core\Queue;
use MonsterMQ\Interfaces\AMQPDispatchers\BasicDispatcher;
use MonsterMQ\Interfaces\Connections\BinaryTransmitter as BinaryTransmitterInterface;
use MonsterMQ\Interfaces\Core\Channel as ChannelInterface;
use MonsterMQ\Interfaces\Connections\Stream as StreamInterface;
use MonsterMQ\Interfaces\Core\Exchange as ExchangeInterface;
use MonsterMQ\Interfaces\Core\Qos as QosInterface;
use MonsterMQ\Interfaces\Core\Queue as QueueInterface;
use MonsterMQ\Interfaces\Core\Session as SessionInterface;
use MonsterMQ\Interfaces\Support\Logger as LoggerInterface;
use MonsterMQ\Support\Logger;

abstract class BaseClient
{
    protected $socket;

    /**
     * @var BinaryTransmitterInterface
     */
    protected $transmitter;

    protected $session;

    protected $channel;

    /**
     * @var \MonsterMQ\Interfaces\Core\Exchange
     */
    protected $exchange;

    /**
     * @var \MonsterMQ\Interfaces\Core\Queue
     */
    protected $queue;

    /**
     * @var \MonsterMQ\Interfaces\Core\Qos
     */
    protected $qos;

    protected $logger;

    protected $basicDispatcher;

    protected $currentChannelNumber = 0;

    public function __construct(
        StreamInterface $socket = null,
        BinaryTransmitterInterface $transmitter = null,
        SessionInterface $session = null,
        ChannelInterface $channel = null,
        ExchangeInterface $exchange = null,
        QueueInterface $queue = null,
        QosInterface $qos = null,
        BasicDispatcher $basicDispatcher = null,
        LoggerInterface $logger = null
    ) {
        $this->setLogger($logger);
		
        $this->setSocket($socket);

        $this->setTransmitter($transmitter);

        $this->setSession($session);

        $this->setChannel($channel);

        $this->setExchange($exchange);

        $this->setQueue($queue);

        $this->basicDispatcher = $basicDispatcher ?? new \MonsterMQ\AMQPDispatchers\BasicDispatcher($this->transmitter, $this);

        $this->setQos($qos);
    }

    public function __destruct()
    {

    }

    protected function setSocket(StreamInterface $socket = null)
    {
        if (is_null($socket)) {
            $this->socket = new Stream($this->logger);
        } else {
            $this->socket = $socket;
        }
    }

    protected function setTransmitter(BinaryTransmitterInterface $transmitter = null)
    {
        if (is_null($transmitter)) {
            $this->transmitter = new BinaryTransmitter($this->socket);
        } else {
            $this->transmitter = $transmitter;
        }
    }

    protected function setSession(SessionInterface $session = null)
    {
        if (is_null($session)) {
            $this->session = new Session(new ConnectionDispatcher($this->transmitter, $this), $this->logger);
        } else {
            $this->session = $session;
        }
    }

    protected function setChannel(ChannelInterface $channel = null)
    {
        if (is_null($channel)) {
            $this->channel = new Channel(new ChannelDispatcher($this->transmitter, $this), $this->session, $this->logger);
        }else{
            $this->channel = $channel;
        }
    }

    protected function setExchange(ExchangeInterface $exchange = null)
    {
        if (!is_null($exchange)) {
            $this->exchange = $exchange;
        } else {
            $this->exchange = new Exchange(new ExchangeDispatcher($this->transmitter, $this), $this, $this->logger);
        }
    }

    protected function setQueue(QueueInterface $queue = null)
    {
        if (!is_null($queue)) {
            $this->queue = $queue;
        } else {
            $this->queue = new Queue(new QueueDispatcher($this->transmitter, $this), $this, $this->logger);
        }
    }

    protected function setQos(QosInterface $qos = null)
    {
        if (!is_null($qos)) {
            $this->qos = $qos;
        } else {
            $this->qos = new Qos($this->basicDispatcher, $this, $this->logger);
        }
    }

    protected function setLogger(LoggerInterface $logger = null)
    {
        if (!is_null($logger)) {
            $this->logger = $logger;
        } else {
            $this->logger = new Logger();
        }
    }

    public function connect(string $address = '127.0.0.1', int $port = 5672, int $connectionTimeout = null)
    {
        $this->socket->connect($address, $port, $connectionTimeout);
    }

    public function logIn(string $username = 'guest', string $password = 'guest')
    {
        if (!$this->socket->isConnected()){
            $this->socket->connect();
        }

        $this->session->logIn($username, $password);

        $this->logger()->write('Session established');

        $this->changeChannel();
    }

    public function changeChannel($channel = null)
    {
        if (!is_null($channel)) {
            if(in_array($channel, ChannelDispatcher::$closedChannels)
                || in_array($channel, ChannelDispatcher::$suspendedChannels)){
                return false;
            } elseif (in_array($channel, ChannelDispatcher::$openedChannels)) {
                return $this->currentChannelNumber = $channel;
            }
        }

        $channel = $this->channel->open($channel);
        $this->currentChannelNumber = $channel;
        $this->logger()->write('Channel changed to '.$channel);
        return $channel;
    }

    public function closeChannel(int $channel)
    {
        $this->channel->close($channel);
        $this->logger()->write("Channel {$channel} closed");
    }

    public function currentChannel()
    {
        return $this->currentChannelNumber;
    }

    /**
     *
     * @return \MonsterMQ\Interfaces\Core\Exchange
     */
    public function newDirectExchange(string $name)
    {
        $this->exchange->setCurrentExchangeName($name);
        $this->exchange->setExchangeType('direct');
        return $this->exchange;
    }

    /**
     *
     * @return \MonsterMQ\Interfaces\Core\Exchange
     */
    public function newFanoutExchange(string $name)
    {
        $this->exchange->setCurrentExchangeName($name);
        $this->exchange->setExchangeType('fanout');
        return $this->exchange;
    }

    /**
     *
     * @return \MonsterMQ\Interfaces\Core\Exchange
     */
    public function newTopicExchange(string $name)
    {
        $this->exchange->setCurrentExchangeName($name);
        $this->exchange->setExchangeType('topic');
        return $this->exchange;
    }

    public function disconnect()
    {
        $this->session()->logOut();
        $this->socket()->close();
    }

    /**
     * @return \MonsterMQ\Interfaces\Connections\Stream
     */
    public function socket()
    {
        return $this->socket;
    }

    /**
     * @return \MonsterMQ\Interfaces\Connections\Stream
     */
    public function network()
    {
        return $this->socket;
    }

    /**
     * @return \MonsterMQ\Interfaces\Core\Session
     */
    public function session()
    {
        return $this->session;
    }

    /**
     * @return \MonsterMQ\Interfaces\Core\Channel
     */
    public function channel()
    {
        return $this->channel;
    }

    /**
     *
     * @return \MonsterMQ\Interfaces\Core\Exchange
     */
    public function exchange(string $name)
    {
        $this->exchange->setCurrentExchangeName($name);
        return $this->exchange;
    }

    /**
     * @param string $queueName
     *
     * @return \MonsterMQ\Interfaces\Core\Queue
     */
    public function queue(string $queueName)
    {
        $this->queue->setCurrentQueueName($queueName);
        return $this->queue;
    }

    /**
     * @return \MonsterMQ\Interfaces\Core\Qos
     */
    public function qos()
    {
        return $this->qos;
    }

    /**
     * @return \MonsterMQ\Interfaces\Support\Logger
     */
    protected function logger()
    {
        return $this->logger;
    }
}