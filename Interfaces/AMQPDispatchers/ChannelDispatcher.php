<?php


namespace MonsterMQ\Interfaces\AMQPDispatchers;


use MonsterMQ\Exceptions\ProtocolException;
use MonsterMQ\Exceptions\SessionException;

interface ChannelDispatcher extends AMQP
{
    /**
     * This method opens a channel to the server.
     *
     * @param int $channel
     */
    public function sendOpen(int $channel);

    /**
     * This method signals to the client that the channel is ready for use.
     *
     * @throws ProtocolException
     * @throws SessionException
     */
    public function receiveOpenOk();

    /**
     * Enable/disable flow from peer.
     *
     * @param int $channel
     * @param bool $active
     */
    public function sendFlow(int $channel, bool $active);

    public function sendFlowOk(int $channel, bool $active);

    public function receiveFlow();

    public function receiveFlowOk();

    public function sendClose();

    public function sendCloseOk();

    public function receiveClose();

    public function receiveCloseOk();
}