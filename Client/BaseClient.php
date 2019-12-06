<?php


namespace MonsterMQ\Client;


use MonsterMQ\AMQPDispatchers\ChannelDispatcher;
use MonsterMQ\AMQPDispatchers\ConnectionDispatcher;
use MonsterMQ\Connections\BinaryTransmitter;
use MonsterMQ\Connections\Stream;
use MonsterMQ\Core\Channel;
use MonsterMQ\Core\Session;
use MonsterMQ\Interfaces\BinaryTransmitter as BinaryTransmitterInterface;
use MonsterMQ\Interfaces\Core\Channel as ChannelInterface;
use MonsterMQ\Interfaces\Stream as StreamInterface;
use MonsterMQ\Interfaces\Core\Session as SessionInterface;


abstract class BaseClient
{
    protected $socket;

    protected $transmitter;

    protected $session;

    protected $channel;

    protected $currentChannelNumber;

    public function __construct(
        StreamInterface $socket = null,
        BinaryTransmitterInterface $transmitter = null,
        SessionInterface $session = null,
        ChannelInterface $channel = null

    ) {
        $this->setSocket($socket);

        $this->setTransmitter($transmitter);

        $this->setSession($session);

        $this->setChannel($channel);
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
            $this->session = new Session(new ConnectionDispatcher($this->socket, $this->transmitter));
        } else {
            $this->session = $session;
        }
    }

    protected function setChannel(ChannelInterface $channel = null)
    {
        if (is_null($channel)) {
            $this->channel = new Channel(new ChannelDispatcher($this->socket, $this->transmitter), $this->session);
        }else{
            $this->channel = $channel;
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
    }

    public function changeChannel(int $channel)
    {
        if (in_array($channel, ChannelDispatcher::$closedChannels)
            || in_array($channel, ChannelDispatcher::$suspendedChannels)
        ) {
            return false;
        } elseif (in_array($channel, ChannelDispatcher::$openedChannels) ) {
            $this->currentChannelNumber = $channel;
        } else {
            $channel = $this->channel->open($channel);
            $this->currentChannelNumber = $channel;
            return $channel;
        }
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

    public function channel()
    {
        return $this->channel;
    }
}