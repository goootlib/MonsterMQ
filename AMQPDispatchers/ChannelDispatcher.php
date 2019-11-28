<?php


namespace MonsterMQ\AMQPDispatchers;

use MonsterMQ\Interfaces\AMQPDispatchers\ChannelDispatcher as ChannelDispatcherInterface;
use MonsterMQ\Interfaces\Connections\BinaryTransmitter as BinaryTransmitterInterface;

/**
 * The ChannelDispatcher class provides methods for a client to establish a
 * channel to a server and for both peers to operate the channel thereafter.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class ChannelDispatcher implements ChannelDispatcherInterface
{
    protected $transmitter;

    public function __construct(BinaryTransmitterInterface $transmitter)
    {
        $this->transmitter = $transmitter;
    }

    public function send_open()
    {

    }

    public function receive_open_ok(){}

    public function send_flow(){}

    public function send_flow_ok(){}

    public function receive_flow(){}

    public function receive_flow_ok(){}

    public function send_close(){}

    public function send_close_ok(){}

    public function receive_close(){}

    public function receive_close_ok(){}
}