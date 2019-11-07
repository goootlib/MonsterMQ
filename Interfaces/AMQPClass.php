<?php


namespace MonsterMQ\Interfaces;


interface AMQPClass
{
    const METHOD_FRAME_TYPE = 1;
    const CONTENT_HEADER_FRAME_TYPE = 2;
    const CONTENT_BODY_FRAME_TYPE = 3;
    const HEARTBEAT_FRAME_TYPE = 8;
}