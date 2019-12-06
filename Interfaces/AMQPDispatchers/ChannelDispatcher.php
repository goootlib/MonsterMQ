<?php


namespace MonsterMQ\Interfaces\AMQPDispatchers;


use MonsterMQ\Exceptions\ProtocolException;
use MonsterMQ\Exceptions\SessionException;

interface ChannelDispatcher extends AMQP
{
    /**
     * This method opens a channel to the server.
     *
     * @param int $channel Channel number to open.
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
     * This method asks the peer to pause or restart the flow of content data
     * sent by a consumer. This is a simple flow-control mechanism that a peer
     * can use to avoid overflowing its queues or otherwise finding itself
     * receiving more messages than it can process. Note that this method is
     * not intended for window control. It does not affect contents returned
     * by Basic.Get-Ok methods.
     *
     * @param int $channel Specified channel.
     * @param bool $active If true, the peer starts sending content frames.
     *                     If false, the peer stops sending content frames.
     *
     * @throws ProtocolException
     */
    public function sendFlow(int $channel, bool $active);

    /**
     * Confirms to the peer that a flow command was received and processed.
     *
     * @return bool $isActive If true, the peer starts sending content frames.
     *                        If false, the peer stops sending content frames.
     *
     * @throws ProtocolException
     * @throws SessionException
     */
    public function receiveFlowOk();

    /**
     * Requests a channel close.
     *
     * @param int         $channel      Channel number to close.
     * @param int|null    $replyCode    Exception reply code.
     * @param string|null $replyMessage Exception reply message.
     * @param int|null    $classId      When the close is provoked by a method
     *                                  exception, this is the class of the
     *                                  method.
     * @param int|null    $methodId     When the close is provoked by a method
     *                                  exception, this is the ID of the method.
     */
    public function sendClose(
        int $channel,
        int $replyCode = null,
        string $replyMessage = null,
        int $classId = null,
        int $methodId = null
    );

    /**
     * Confirms a channel close.
     *
     * @throws ProtocolException
     * @throws SessionException
     */
    public function receiveCloseOk();
}