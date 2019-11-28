<?php


namespace MonsterMQ\Interfaces\AMQPDispatchers;


interface ChannelDispatcher extends AMQP
{
    public const CLASS_ID = 20;

    public const OPEN_METHOD_ID = 10;
    public const OPEN_OK_METHOD_ID = 11;
    public const FLOW_METHOD_ID = 20;
    public const FLOW_OK_METHOD_ID = 21;
    public const CLOSE_METHOD_ID = 40;
    public const CLOSE_OK_METHOD_ID = 41;

    public function send_open();

    public function receive_open_ok();

    public function send_flow();

    public function send_flow_ok();

    public function receive_flow();

    public function receive_flow_ok();

    public function send_close();

    public function send_close_ok();

    public function receive_close();

    public function receive_close_ok();
}