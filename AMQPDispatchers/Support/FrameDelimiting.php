<?php


namespace MonsterMQ\AMQPDispatchers\Support;


use MonsterMQ\Exceptions\ProtocolException;

/**
 * This trait responsible for frame delimiting and validation of delimiting.
 *
 * @author Gleb zhukov <goootlib@gmail.com>
 */
trait FrameDelimiting
{
    /**
     * Frame delimiter validation is required by AMQP. So we supposed to
     * validate each incoming frame delimiter.
     *
     * @throws ProtocolException
     */
    protected function validateFrameDelimiter()
    {
        if("\xCE" != $this->transmitter->receiveRaw(1)){
            throw new ProtocolException(
                'Frame delimiter is invalid. It must be 0xCE.'
            );
        };
    }

    /**
     * This method must complete each frame transmission.
     */
    protected function sendFrameDelimiter()
    {
        $this->transmitter->sendRaw("\xCE");
    }
}