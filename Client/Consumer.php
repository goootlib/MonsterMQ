<?php

namespace MonsterMQ\Client;

/**
 * This class provides consuming API for end-users.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class Consumer extends BaseClient
{

    /**
     * If this field is set the server does not expect acknowledgements for
     * messages. That is, when a message is delivered to the client the server
     * assumes the delivery will succeed and immediately dequeues it.
     *
     * @var bool
     */
    protected $noAck = false;

    /**
     * Consumer tag.
     *
     * @var array
     */
    protected $consumerTags = [];

    /**
     * Starts consuming a queue.
     *
     * @param $queue Queue to consume.
     *
     * @return string Consumer tag.
     *
     * @throws \MonsterMQ\Exceptions\ConnectionException
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function startConsume($queue)
    {
        $this->basicDispatcher->sendConsume($this->currentChannel(), $queue, '', false, $this->noAck, false, false, []);
        $this->consumerTags[$this->currentChannel()][] = $this->basicDispatcher->receiveConsumeOk();
        return end($this->consumerTags[$this->currentChannel()]);
    }

    /**
     * Stops consuming a queue.
     *
     * @param string $consumerTag Consumer tag.
     *
     * @throws \MonsterMQ\Exceptions\ConnectionException
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function stopConsume($consumerTag = null)
    {
        if (!is_null($consumerTag)) {
            $this->basicDispatcher->sendCancel($this->currentChannel(), $consumerTag, false);
            $this->basicDispatcher->receiveCancelOk();
        } else {
            foreach ($this->consumerTags[$this->currentChannel()] as $tag) {
                $this->basicDispatcher->sendCancel($this->currentChannel(), $tag, false);
                $this->basicDispatcher->receiveCancelOk();
            }
        }
    }

    /**
     * Starts consuming loop which waits for incoming messages.
     *
     * @param \Closure $handler Closure that handles incoming messages.
     *
     * @throws \MonsterMQ\Exceptions\ConnectionException
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function wait(\Closure $handler)
    {
        while ($arguments = $this->basicDispatcher->receiveMessage()) {
            call_user_func_array($handler, $arguments);
        }
    }

    /**
     * Disables acknowledgements for incoming messages.
     *
     * @return $this
     */
    public function noAck()
    {
        $this->noAck = true;
        return $this;
    }
}