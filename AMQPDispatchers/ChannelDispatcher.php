<?php


namespace MonsterMQ\AMQPDispatchers;

use MonsterMQ\AMQPDispatchers\Support\FrameDelimiting;
use MonsterMQ\AMQPDispatchers\Support\UnexpectedHandler;
use MonsterMQ\AMQPDispatchers\Support\HeartbeatHandler;
use MonsterMQ\Exceptions\ProtocolException;
use MonsterMQ\Interfaces\AMQPDispatchers\ChannelDispatcher as ChannelDispatcherInterface;
use MonsterMQ\Interfaces\Connections\BinaryTransmitter as BinaryTransmitterInterface;

/**
 * The ChannelDispatcher class provides methods for a client to establish a
 * channel to a server and for both peers to operate the channel thereafter.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class ChannelDispatcher implements ChannelDispatcherInterface
{
    use FrameDelimiting;
    use HeartbeatHandler;
    use UnexpectedHandler;

    /**
     * Binary transmitter instance.
     *
     * @var BinaryTransmitterInterface
     */
    protected $transmitter;

    public function __construct(BinaryTransmitterInterface $transmitter)
    {
        $this->transmitter = $transmitter;
    }

    public function send_open(int $channel)
    {
        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CLASS_ID);
        $this->transmitter->sendShort(static::CHANNEL_OPEN);
        $this->transmitter->sendShortStr('');
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();
        $this->sendFrameDelimiter();
    }

    public function receive_open_ok()
    {
        $frameType = $this->receiveFrameType();
        $channel = $this->transmitter->receiveShort();
        $size = $this->transmitter->receiveLong();

        [$classId, $methodId] = $this->receiveClassAndMethod();
        if ($classId != static::CLASS_ID || $methodId != static::CHANNEL_OPEN_OK) {
            throw new ProtocolException(
                "\"Unexpected method frame. Expecting class id '20' and method 
                id '11'. '{$classId}' and '{$methodId}' given.\"");
        }
        $this->transmitter->receiveLongStr();

        $this->validateFrameDelimiter();
    }

    public function send_flow(int $channel, bool $active)
    {
        $active = $active ? 1 : 0;

        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CLASS_ID);
        $this->transmitter->sendShort(static::CHANNEL_FLOW);
        $this->transmitter->sendOctet($active);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();
        $this->sendFrameDelimiter();
    }

    public function send_flow_ok(int $channel, bool $active)
    {
        $active = $active ? 1 : 0;

        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CLASS_ID);
        $this->transmitter->sendShort(static::CHANNEL_FLOW_OK);
        $this->transmitter->sendOctet($active);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();
        $this->sendFrameDelimiter();
    }

    public function receive_flow()
    {
        $frameType = $this->receiveFrameType();
        $channel = $this->transmitter->receiveShort();
        $size = $this->transmitter->receiveLong();

        [$classId, $methodId] = $this->receiveClassAndMethod();
        $isActive = $this->transmitter->receiveOctet();
        $this->validateFrameDelimiter();

        $this->send_flow_ok($channel, $isActive);
    }

    public function receive_flow_ok()
    {
        $frameType = $this->receiveFrameType();
        $channel = $this->transmitter->receiveShort();
        $size = $this->transmitter->receiveLong();

        [$classId, $methodId] = $this->receiveClassAndMethod();
        $isActive = $this->transmitter->receiveOctet();

        $this->validateFrameDelimiter();
    }

    public function send_close()
    {

    }

    public function send_close_ok(){}

    public function receive_close(){}

    public function receive_close_ok(){}
}