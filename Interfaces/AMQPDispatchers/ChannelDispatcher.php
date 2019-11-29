<?php


namespace MonsterMQ\Interfaces\AMQPDispatchers;


interface ChannelDispatcher extends AMQP
{
    public const CLASS_ID = 20;

    public const CHANNEL_OPEN = 10;
    public const CHANNEL_OPEN_OK = 11;
    public const CHANNEL_FLOW = 20;
    public const CHANNEL_FLOW_OK = 21;
    public const CHANNEL_CLOSE = 40;
    public const CHANNEL_CLOSE_OK = 41;

    public function send_open(int $channel);

    public function receive_open_ok();

    public function send_flow(int $channel, bool $active);

    public function send_flow_ok(int $channel, bool $active);

    public function receive_flow();

    public function receive_flow_ok();

    public function send_close();

    public function send_close_ok();

    public function receive_close();

    public function receive_close_ok();
}