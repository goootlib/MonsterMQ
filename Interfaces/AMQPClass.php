<?php


namespace MonsterMQ\Interfaces;


interface AMQPClass
{
    public const METHOD_FRAME_TYPE = 1;
    public const CONTENT_HEADER_FRAME_TYPE = 2;
    public const CONTENT_BODY_FRAME_TYPE = 3;
    public const HEARTBEAT_FRAME_TYPE = 8;

    public const FRAME_DELIMITER = "\xCE";
}