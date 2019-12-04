<?php


namespace MonsterMQ\AMQPDispatchers;

use MonsterMQ\Exceptions\ConnectionException;
use MonsterMQ\Exceptions\ProtocolException;
use MonsterMQ\Exceptions\SessionException;
use MonsterMQ\Interfaces\AMQPDispatchers\AMQP;
use MonsterMQ\Interfaces\Connections\BinaryTransmitter as BinaryTransmitterInterface;
use MonsterMQ\Interfaces\Connections\Stream as StreamInterface;

/**
 * This is a base class for AMQP dispatchers, it includes common logic needed
 * for every concrete AMQP dispatchers.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
abstract class BaseDispatcher implements AMQP
{
    /**
     * Stream connection instance
     *
     * @var StreamInterface
     */
    protected $socket;

    /**
     * Binary transmitter instance.
     *
     * @var BinaryTransmitterInterface
     */
    protected $transmitter;

    /**
     * Timeout after which session and socket connection will be closed
     * without waiting for incoming close-ok method.
     *
     * @var int
     */
    protected $closeOkTimeout = 130;

    /**
     * Current frame size used by termination methods.
     *
     * @var int
     */
    protected $currentFrameSize;

    /**
     * Current channel number.
     *
     * @var int
     */
    protected $currentChannel;

    /**
     * Channels that are not available for transmitting messages.
     *
     * @var array
     */
    public $suspendedChannels = [];

    /**
     * BaseDispatcher constructor.
     *
     * @param StreamInterface $socket
     * @param BinaryTransmitterInterface $transmitter
     */
    public function __construct(StreamInterface $socket, BinaryTransmitterInterface $transmitter)
    {
        $this->socket = $socket;
        $this->transmitter = $transmitter;
    }

    /**
     * Sets timeout after which connection will be closed without waiting for
     * incoming close-ok method.
     *
     * @param int $timeout
     */
    public function setCloseOkTimeout(int $timeout)
    {
        $this->closeOkTimeout = $timeout;
    }

    /**
     * Stores current frame size.
     *
     * @param int $size
     */
    public function setCurrentFrameSize(int $size)
    {
        $this->currentFrameSize = $size;
    }

    /**
     * Sets current channel number
     *
     * @param int $channel Current channel number.
     */
    public function setCurrentChannel(int $channel)
    {
        $this->currentChannel = $channel;
    }

    /**
     * Skips heartbeat frames, and returns frame types of other incoming
     * frames.
     *
     * @return int Frame type of incoming frame.
     *
     * @throws ProtocolException
     */
    protected function receiveFrameType(): ?int
    {
        while (!is_null($frametype = $this->transmitter->receiveOctet())) {
            if ($frametype == static::HEARTBEAT_FRAME_TYPE) {
                $this->transmitter->receiveShort();
                $this->transmitter->receiveLong();
                $this->validateFrameDelimiter();
                continue;
            } else {
                return $frametype;
            }
        }
        return null;
    }

    /**
     * Handles methods which comes instead of expecting ones. Otherwise returns
     * AMQP class id and method id as associative array.
     *
     * @return array First element AMQP class id, second element AMQP method id
     *
     * @throws SessionException
     * @throws ProtocolException
     */
    protected function receiveClassAndMethod(): array
    {
        do {
            $classId = $this->transmitter->receiveShort();
            $methodId = $this->transmitter->receiveShort();

            $connectionClosure = ($classId == static::CONNECTION_CLASS_ID) && ($methodId == static::CONNECTION_CLOSE);
            $flowControl = ($classId == static::CHANNEL_CLASS_ID) && ($methodId == static::CHANNEL_FLOW);

            if ($connectionClosure) {
                $this->handleConnectionClose();
            }

            if ($flowControl) {
                $this->handleChannelFlow();
            }

        } while ($connectionClosure || $flowControl);

        return [$classId, $methodId];
    }

    /**
     * Handshakes Connection Close method. And throws exception.
     *
     * @throws ProtocolException
     * @throws SessionException
     */
    protected function handleConnectionClose()
    {
        $this->sendConnectionCloseOk();
        $this->close();
    }

    /**
     * Handles incoming channel flow method.
     *
     * @throws ProtocolException
     */
    protected function handleChannelFlow()
    {
        $isActive = $this->transmitter->receiveOctet();
        $this->validateFrameDelimiter();

        $this->sendChannelFlowOk($this->currentChannel, $isActive);

        if ($isActive) {
            $this->suspendedChannels = array_diff($this->suspendedChannels, [$this->currentChannel]);
        } else {
            $this->suspendedChannels[] = $this->currentChannel;
        }
    }

    /**
     * This method confirms a Connection.Close method and tells the recipient
     * that it is safe to release resources for the connection and close the
     * socket.
     */
    public function sendConnectionCloseOk()
    {
        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort(static::SYSTEM_CHANNEL);
        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CONNECTION_CLASS_ID);
        $this->transmitter->sendShort(static::CONNECTION_CLOSE_OK);
        $this->transmitter->disableBuffering();
        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();
        $this->sendFrameDelimiter();
    }

    /**
     * Confirms to the peer that a flow command was received and processed.
     *
     * @param int $channel Specified channel.
     * @param bool $active If 1, the peer starts sending content frames.
     *                     If 0, the peer stops sending content frames.
     */
    public function sendChannelFlowOk(int $channel, bool $active)
    {
        $active = $active ? 1 : 0;

        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CHANNEL_CLASS_ID);
        $this->transmitter->sendShort(static::CHANNEL_FLOW_OK);
        $this->transmitter->sendOctet($active);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();
        $this->sendFrameDelimiter();
    }

    /**
     * Throws an exception which indicates that session was closed.
     *
     * @throws SessionException
     * @throws ProtocolException
     */
    protected function close()
    {
        $this->transmitter->enableBuffering();
        //Subtract 2 bytes as AMQP class id and yet 2 bytes as AMQP method id
        $this->transmitter->receiveIntoBuffer($this->currentFrameSize - 4);
        $this->validateFrameDelimiter();

        $replyCode = $this->transmitter->receiveShort();
        $replyMessage = $this->transmitter->receiveShortStr();
        $exceptionClassId = $this->transmitter->receiveShort();
        $exceptionMethodId = $this->transmitter->receiveShort();
        $this->transmitter->disableBuffering();

        $msg = $exceptionClassId && $exceptionMethodId
            ? "And exception class id '{$exceptionClassId}', exception method id '{$exceptionMethodId}'."
            : "";

        throw new SessionException(
            "Server closes the connection with reply code '{$replyCode}' and
                 message '{$replyMessage}'. ".$msg
        );
    }

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
