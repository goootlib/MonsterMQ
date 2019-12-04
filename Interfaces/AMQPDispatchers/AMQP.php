<?php


namespace MonsterMQ\Interfaces\AMQPDispatchers;


interface AMQP
{
    public const METHOD_FRAME_TYPE = 1;
    public const CONTENT_HEADER_FRAME_TYPE = 2;
    public const CONTENT_BODY_FRAME_TYPE = 3;
    public const HEARTBEAT_FRAME_TYPE = 8;

    public const SYSTEM_CHANNEL = 0;

    public const FRAME_DELIMITER = "\xCE";

    public const CONNECTION_CLASS_ID = 10;

    public const CONNECTION_START = 10;
    public const CONNECTION_START_OK = 11;
    public const CONNECTION_TUNE = 30;
    public const CONNECTION_TUNE_OK = 31;
    public const CONNECTION_OPEN = 40;
    public const CONNECTION_OPEN_OK = 41;
    public const CONNECTION_CLOSE = 50;
    public const CONNECTION_CLOSE_OK = 51;

    public const CHANNEL_CLASS_ID = 20;

    public const CHANNEL_OPEN = 10;
    public const CHANNEL_OPEN_OK = 11;
    public const CHANNEL_FLOW = 20;
    public const CHANNEL_FLOW_OK = 21;
    public const CHANNEL_CLOSE = 40;
    public const CHANNEL_CLOSE_OK = 41;
}