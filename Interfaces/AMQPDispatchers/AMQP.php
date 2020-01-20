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

    public const EXCHANGE_CLASS_ID = 40;

    public const EXCHANGE_DECLARE = 10;
    public const EXCHANGE_DECLARE_OK = 11;
    public const EXCHANGE_DELETE = 20;
    public const EXCHANGE_DELETE_OK = 21;
    public const EXCHANGE_BIND = 30;
    public const EXCHANGE_BIND_OK = 31;
    public const EXCHANGE_UNBIND = 40;
    public const EXCHANGE_UNBIND_OK = 51;

    public const QUEUE_CLASS_ID = 50;

    public const QUEUE_DECLARE = 10;
    public const QUEUE_DECLARE_OK = 11;
    public const QUEUE_BIND = 20;
    public const QUEUE_BIND_OK = 21;
    public const QUEUE_UNBIND = 50;
    public const QUEUE_UNBIND_OK = 51;
    public const QUEUE_PURGE = 30;
    public const QUEUE_PURGE_OK = 31;
    public const QUEUE_DELETE = 40;
    public const QUEUE_DELETE_OK = 41;

    public const BASIC_CLASS_ID = 60;

    public const BASIC_QOS = 10;
    public const BASIC_QOS_OK = 11;
    public const BASIC_CONSUME = 20;
    public const BASIC_CONSUME_OK = 21;
    public const BASIC_CANCEL = 30;
    public const BASIC_CANCEL_OK = 31;
    public const BASIC_PUBLISH = 40;
    public const BASIC_RETURN = 50;
    public const BASIC_DELIVER = 60;
    public const BASIC_GET = 70;
    public const BASIC_GET_OK = 71;
    public const BASIC_GET_EMPTY = 72;
    public const BASIC_ACK = 80;
    public const BASIC_REJECT = 90;
    public const BASIC_RECOVER = 110;
    public const BASIC_RECOVER_OK = 111;
    public const BASIC_NACK = 120;

    public const TX_CLASS_ID = 90;

    public const TX_SELECT = 10;
    public const TX_SELECT_OK = 11;
    public const TX_COMMIT = 20;
    public const TX_COMMIT_OK = 21;
    public const TX_ROLLBACK = 30;
    public const TX_ROLLBACK_OK = 31;
}