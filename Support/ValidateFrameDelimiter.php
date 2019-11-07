<?php


namespace MonsterMQ\Support;


use MonsterMQ\Exceptions\ProtocolException;

trait ValidateFrameDelimiter
{
    /**
     * Frame delimiter validation is required by AMQP. So we will validate each
     * incoming frame delimiter.
     *
     * @throws ProtocolException
     */
    public function validateFrameDelimiter()
    {
        if("\xCE" != $this->transmitter->receiveRaw(1)){
            throw new ProtocolException('Frame delimiter is invalid. It must be 0xCE.');
        };
    }
}