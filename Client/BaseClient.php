<?php

namespace MonsterMQ\Client;

use MonsterMQ\AMQPDispatchers\ChannelDispatcher;
use MonsterMQ\AMQPDispatchers\ConnectionDispatcher;
use MonsterMQ\AMQPDispatchers\ExchangeDispatcher;
use MonsterMQ\Connections\BinaryTransmitter;
use MonsterMQ\Connections\Stream;
use MonsterMQ\Core\Channel;
use MonsterMQ\Core\Exchange;
use MonsterMQ\Core\ExchangeDeclarator;
use MonsterMQ\Core\Session;
use MonsterMQ\Interfaces\Connections\BinaryTransmitter as BinaryTransmitterInterface;
use MonsterMQ\Interfaces\Core\Channel as ChannelInterface;
use MonsterMQ\Interfaces\Connections\Stream as StreamInterface;
use MonsterMQ\Interfaces\Core\Exchange as ExchangeInterface;
use MonsterMQ\Interfaces\Core\ExchangeDeclarator as ExchangeDeclaratorInterface;
use MonsterMQ\Interfaces\Core\Session as SessionInterface;

abstract class BaseClient
{
    protected $socket;

    protected $transmitter;

    protected $session;

    protected $channel;

    protected $exchangeDeclarator;

    protected $exchange;

    protected $currentChannelNumber = 0;

    public function __construct(
        StreamInterface $socket = null,
        BinaryTransmitterInterface $transmitter = null,
        SessionInterface $session = null,
        ChannelInterface $channel = null,
        ExchangeDeclaratorInterface $exchangeDeclarator = null,
        ExchangeInterface $exchange = null
    ) {
        $this->setSocket($socket);

        $this->setTransmitter($transmitter);

        $this->setSession($session);

        $this->setChannel($channel);

        $this->setExchangeDeclarator($exchangeDeclarator);

        $this->setExchange($exchange);
    }

    public function __destruct()
    {

    }

    protected function setSocket(StreamInterface $socket = null)
    {
        if (is_null($socket)) {
            $this->socket = new Stream();
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
            $this->session = new Session(new ConnectionDispatcher($this->transmitter));
        } else {
            $this->session = $session;
        }
    }

    protected function setChannel(ChannelInterface $channel = null)
    {
        if (is_null($channel)) {
            $this->channel = new Channel(new ChannelDispatcher($this->transmitter), $this->session);
        }else{
            $this->channel = $channel;
        }
    }

    protected function setExchangeDeclarator(ExchangeDeclaratorInterface $exchangeDeclarator = null)
    {
        if (!is_null($exchangeDeclarator)) {
            $this->exchangeDeclarator = $exchangeDeclarator;
        } else {
            $this->exchangeDeclarator = new ExchangeDeclarator(new ExchangeDispatcher($this->transmitter), $this);
        }
    }

    protected function setExchange(ExchangeInterface $exchange = null)
    {
        if (!is_null($exchange)) {
            $this->exchange = $exchange;
        } else {
            $this->exchange = new Exchange(new ExchangeDispatcher($this->transmitter), $this);
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

        $this->changeChannel(1);
    }

    public function changeChannel(int $channel = null)
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
        return $channel;

    }

    public function currentChannel()
    {
        return $this->currentChannelNumber;
    }

    /**
     * @param string $name
     * @return \MonsterMQ\Core\ExchangeDeclarator
     */
    public function newDirectExchange(string $name)
    {
        $this->exchangeDeclarator->setName($name);
        $this->exchangeDeclarator->setType('direct');
        return $this->exchangeDeclarator;
    }

    /**
     * @param string $name
     * @return \MonsterMQ\Core\ExchangeDeclarator
     */
    public function newFanoutExchange(string $name)
    {
        $this->exchangeDeclarator->setName($name);
        $this->exchangeDeclarator->setType('fanout');
        return $this->exchangeDeclarator;
    }

    /**
     * @param string $name
     * @return \MonsterMQ\Core\ExchangeDeclarator
     */
    public function newTopicExchange(string $name)
    {
        $this->exchangeDeclarator->setName($name);
        $this->exchangeDeclarator->setType('topic');
        return $this->exchangeDeclarator;
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
     * @param $currentExchange
     *
     * @return \MonsterMQ\Interfaces\Core\Exchange
     */
    public function exchange($currentExchange)
    {
        $this->exchange->setCurrentExchange($currentExchange);
        return $this->exchange;
    }
}