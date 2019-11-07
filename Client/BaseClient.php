<?php


namespace MonsterMQ;


use MonsterMQ\Connections\BinaryTransmitter;
use MonsterMQ\Connections\Stream;
use MonsterMQ\Interfaces\BinaryTransmitter as BinaryTransmitterInterface;
use MonsterMQ\Interfaces\Stream as StreamInterface;


abstract class BaseClient
{
    protected $socket;

    protected $transmitter;

    public function __construct(StreamInterface $socket = null, BinaryTransmitterInterface $transmitter = null)
    {
        if(is_null($socket)){
            $this->socket = new Stream();
        }
        if(is_null($transmitter)){
            $this->transmitter = new BinaryTransmitter();
        }
    }


    public function connect($address = '127.0.0.1', $port = 5672, $connectionTimeout = null)
    {
        $this->socket->connect($address, $port, $connectionTimeout);
    }

    public function logIn($username = 'guest', $password = 'guest')
    {

    }

    public function socket()
    {
        return $this->socket;
    }

    public function network()
    {
        return $this->socket;
    }
}