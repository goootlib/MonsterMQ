<?php


namespace MonsterMQ\AMQPDispatchers;

use MonsterMQ\Exceptions\ProtocolException;
use MonsterMQ\Exceptions\SessionException;
use MonsterMQ\Interfaces\AMQPDispatchers\ChannelDispatcher as ChannelDispatcherInterface;

/**
 * The ChannelDispatcher class provides methods for a client to establish a
 * channel to a server and for both peers to operate the channel thereafter.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class ChannelDispatcher extends BaseDispatcher implements ChannelDispatcherInterface
{
    /**
     * This method opens a channel to the server.
     *
     * @param int $channel Channel number to open.
     */
    public function sendOpen(int $channel)
    {
        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CHANNEL_CLASS_ID);
        $this->transmitter->sendShort(static::CHANNEL_OPEN);
        //Following short string argument reserved by AMQP
        $this->transmitter->sendShortStr('');
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();
        $this->sendFrameDelimiter();
    }

    /**
     * This method signals to the client that the channel is ready for use.
     *
     * @throws ProtocolException
     * @throws SessionException
     */
    public function receiveOpenOk()
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if ($classId != static::CHANNEL_CLASS_ID || $methodId != static::CHANNEL_OPEN_OK) {
            throw new ProtocolException(
                "\"Unexpected method frame. Expecting class id '20' and method 
                id '11'. '{$classId}' and '{$methodId}' given.\"");
        }
        //Following argument reserved by AMQP
        $this->transmitter->receiveLongStr();

        $this->validateFrameDelimiter();
    }

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
     */
    public function sendFlow(int $channel, bool $active)
    {
        $active = $active ? 1 : 0;

        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CHANNEL_CLASS_ID);
        $this->transmitter->sendShort(static::CHANNEL_FLOW);
        $this->transmitter->sendOctet($active);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }

    /**
     * Confirms to the peer that a flow command was received and processed.
     *
     * @return bool $isActive If true, the peer starts sending content frames.
     *                        If false, the peer stops sending content frames.
     *
     * @throws ProtocolException
     * @throws SessionException
     */
    public function receiveFlowOk(): bool
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if ($classId != static::CHANNEL_CLASS_ID && $methodId != static::CHANNEL_FLOW_OK) {
            throw new ProtocolException("Unexpected method frame. Expecting class id '20' and method 
                id '21'. '{$classId}' and '{$methodId}' given.");
        }

        $isActive = $this->transmitter->receiveOctet();

        $this->validateFrameDelimiter();

        return (bool) $isActive;
    }

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
    ) {
        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CHANNEL_CLASS_ID);
        $this->transmitter->sendShort(static::CHANNEL_CLOSE);
        $this->transmitter->sendShort($replyCode);
        $this->transmitter->sendShortStr($replyMessage);
        $this->transmitter->sendShort($classId);
        $this->transmitter->sendShort($methodId);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }

    /**
     * Confirms a channel close.
     *
     * @throws ProtocolException
     * @throws SessionException
     */
    public function receiveCloseOk()
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if ($classId != static::CHANNEL_CLASS_ID && $methodId != static::CHANNEL_CLOSE_OK) {
            throw new ProtocolException("Unexpected method frame. Expecting 
                class id '20' and method id '41'. '{$classId}' and '{$methodId}' given."
            );
        }

        $this->validateFrameDelimiter();
    }
}