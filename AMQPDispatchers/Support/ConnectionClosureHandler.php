<?php


namespace MonsterMQ\AMQPDispatchers\Support;

/**
 * This trait responsible for handling connection closure.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
trait ConnectionClosureHandler
{
    /**
     * Handles Connection.Close method if it comes instead of expecting
     * method. Otherwise returns AMQP class id and method id as associative
     * array.
     *
     * @return array First element AMQP class id, second element AMQP method id
     */
    protected function receiveClassAndMethod(): array
    {
        $classId = $this->transmitter->receiveShort();
        $methodId = $this->transmitter->receiveShort();
        if ($classId == static::CLASS_ID && $methodId == static::CLOSE_METHOD_ID) {
            $this->send_close_ok();
        } else {
            return [$classId,$methodId];
        }
    }
}