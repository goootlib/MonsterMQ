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
    public function consume($queue)
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
     * Synchronously obtains of messages.
     *
     * @param string $queue Queue from which message will be obtained.
     *
     * @return array|false Returns array which first element is message and
     *                     second is channel number. If false was returned
     *                     it means requested queue is empty.
     *
     * @throws \MonsterMQ\Exceptions\ConnectionException
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function get(string $queue)
    {
        $this->basicDispatcher->sendGet($this->currentChannel(), $queue, $this->noAck);
        return $this->basicDispatcher->receiveGetOkOrGetEmpty();
    }

    /**
     * Acknowledges last message.
     */
    public function ackLast()
    {
        $this->basicDispatcher->sendAck($this->currentChannel(), null, false);
    }

    /**
     * Acknowledges all outstanding message.
     */
    public function ackAll()
    {
        $this->basicDispatcher->sendAck($this->currentChannel(), 0, true);
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

    /**
     * This method allows a client to reject last incoming message.
     */
    public function rejectLast($requeue = false)
    {
        $this->basicDispatcher->sendNack($this->currentChannel(), null, false, $requeue);
    }

    /**
     * This method allows a client to reject all unacknowledged message.
     */
    public function rejectAll($requeue = false)
    {
        $this->basicDispatcher->sendNack($this->currentChannel(), 0, true, $requeue);
    }

    /**
     * This method asks the server to redeliver all unacknowledged messages
     * on a specified channel.
     *
     * @param bool $requeue If this argument is false, the message will be redelivered
     *                      to the original recipient. If this argument is true, the server
     *                      will attempt to requeue the message, potentially then
     *                      delivering it to an alternative subscriber.
     *
     * @throws \MonsterMQ\Exceptions\ConnectionException
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function redeliver($requeue = false)
    {
        $this->basicDispatcher->sendRecover($this->currentChannel(), $requeue);
        $this->basicDispatcher->receiveRecoverOk();
    }
}