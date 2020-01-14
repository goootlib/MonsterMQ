<?php


namespace MonsterMQ\AMQPDispatchers;

use MonsterMQ\Client\BaseClient;
use MonsterMQ\Exceptions\ConnectionException;
use MonsterMQ\Exceptions\ProtocolException;
use MonsterMQ\Exceptions\SessionException;
use MonsterMQ\Interfaces\AMQPDispatchers\AMQP;
use MonsterMQ\Interfaces\Connections\BinaryTransmitter as BinaryTransmitterInterface;
use MonsterMQ\Interfaces\Core\Session as SessionInterface;

/**
 * This is a base class for AMQP dispatchers, it includes common logic needed
 * for every concrete AMQP dispatchers.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
abstract class BaseDispatcher implements AMQP
{
    /**
     * Binary transmitter instance.
     *
     * @var BinaryTransmitterInterface
     */
    protected $transmitter;

    /**
     * Client instance.
     *
     * @var BaseClient
     */
    protected $client;

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
     * Timestamp of last socket read.
     *
     * @var int
     */
    protected $lastRead;

    /**
     * Channels that were opened.
     *
     * @var array
     */
    public static $openedChannels = [];

    /**
     * Channels that are open but not available for transmitting messages.
     *
     * @var array
     */
    public static $suspendedChannels = [];

    /**
     * Closed channels.
     *
     * @var array
     */
    public static $closedChannels = [];

    /**
     * BaseDispatcher constructor.
     *
     * @param BinaryTransmitterInterface $transmitter
     * @param BaseClient $client
     */
    public function __construct(BinaryTransmitterInterface $transmitter, BaseClient $client)
    {
        $this->transmitter = $transmitter;
        $this->client = $client;
    }

    /**
     * Sets current channel number.
     *
     * @param int $channel Current channel number.
     */
    protected function setCurrentChannel(int $channel)
    {
        $this->currentChannel = $channel;
    }

    /**
     * Returns current channel number.
     *
     * @return int
     */
    public function currentChannel(): int
    {
        return $this->currentChannel;
    }

    /**
     * Validates and stores current frame size.
     *
     * @param int $size
     *
     * @throws ProtocolException
     */
    protected function setCurrentFrameSize(int $size)
    {
        $this->validateFrameSize($size);
        $this->currentFrameSize = $size;
    }

    /**
     * Validates frame size.
     *
     * @param $size Frame size.
     *
     * @throws ProtocolException
     */
    protected function validateFrameSize($size)
    {
        if (!is_null($this->client->session()->frameMaxSize()) && $size > $this->client->session()->frameMaxSize()) {
            throw new ProtocolException(
                "Frame size exceeded negotiated frame max size value. 
                Negotiated value is ".$this->session->frameMaxSize().". 
                Incoming frame size value is ".$size
            );
        }
    }

    /**
     * Returns current frame size.
     *
     * @return int
     */
    public function currentFrameSize(): int
    {
        return $this->currentFrameSize;
    }

    /**
     * Skips heartbeat frames, and returns frame types of other incoming
     * frames.
     *
     * @return int Frame type of incoming frame.
     *
     * @throws ProtocolException
     * @throws ConnectionException
     */
    protected function receiveFrameType(): ?int
    {
        $this->lastRead = time();

        while (!is_null($frametype = $this->transmitter->receiveOctet())) {
            if ($frametype == static::HEARTBEAT_FRAME_TYPE) {
                $this->transmitter->receiveShort();
                $this->transmitter->receiveLong();
                $this->validateFrameDelimiter();
                $this->sendHeartbeat();
                $this->lastRead = time();
                continue;
            } elseif ($frametype == static::CONTENT_HEADER_FRAME_TYPE) {
                $this->lastRead = time();
                $this->sendHeartbeat();
                return $frametype;
            } else {
                $this->lastRead = time();
                return $frametype;
            }
        }

        $heartbeatInterval = $this->client->session()->heartbeatInterval() ?? 60;
        $timeout = $heartbeatInterval * 2;
        if (time() >= $this->lastRead + $timeout) {
            throw new ConnectionException(
                "Heartbeat missed. Server does not respond."
            );
        }

        return null;
    }

    /**
     * Sends heartbeat frame.
     */
    protected function sendHeartbeat()
    {
        $this->transmitter->sendOctet(self::HEARTBEAT_FRAME_TYPE);
        $this->transmitter->sendShort(self::SYSTEM_CHANNEL);
        $this->transmitter->sendLong(0);
        $this->sendFrameDelimiter();
    }

    /**
     * Handles methods which comes instead of expecting ones. Otherwise returns
     * AMQP class id and method id as associative array.
     *
     * @return array First element AMQP class id, second element AMQP method id
     *
     * @throws SessionException
     * @throws ProtocolException
     * @throws ConnectionException
     */
    public function receiveClassAndMethod(): array
    {
        do {
            $this->receiveFrameType();
            $this->setCurrentChannel($this->transmitter->receiveShort());
            $this->setCurrentFrameSize($this->transmitter->receiveLong());

            $classId = $this->transmitter->receiveShort();
            $methodId = $this->transmitter->receiveShort();

            $connectionClosure = ($classId == static::CONNECTION_CLASS_ID) && ($methodId == static::CONNECTION_CLOSE);
            $flowControl = ($classId == static::CHANNEL_CLASS_ID) && ($methodId == static::CHANNEL_FLOW);
            $channelClosure = ($classId == static::CHANNEL_CLASS_ID) && ($methodId == static::CHANNEL_CLOSE);
            $ackIncome = ($classId == self::BASIC_CLASS_ID) && ($methodId == self::BASIC_ACK);

            if ($connectionClosure) {
                $this->handleConnectionClose();
            }

            if ($flowControl) {
                $this->handleChannelFlow();
            }

            if ($channelClosure) {
                $this->handleChannelClosure();
            }

            if ($ackIncome) {
                $this->handleAckIncome();
            }

        } while ($connectionClosure || $flowControl || $channelClosure || $ackIncome);

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

        $this->sendChannelFlowOk($isActive);

        if ($isActive) {
            self::$suspendedChannels = array_diff(self::$suspendedChannels, [$this->currentChannel]);
        } else {
            self::$suspendedChannels[] = $this->currentChannel;
        }
    }

    /**
     * Handles incoming channel closure method.
     *
     * @throws ProtocolException
     */
    protected function handleChannelClosure()
    {
        $this->transmitter->receiveShort();
        $this->transmitter->receiveShortStr();
        $this->transmitter->receiveShort();
        $this->transmitter->receiveShort();

        $this->validateFrameDelimiter();

        self::$closedChannels[] = $this->currentChannel;

        $this->sendChannelCloseOk();
    }

    /**
     * Handles incoming of acknowledgement.
     *
     * @throws ProtocolException
     */
    protected function handleAckIncome()
    {
        $this->transmitter->receiveLongLong();
        $this->transmitter->receiveOctet();

        $this->validateFrameDelimiter();
    }

    /**
     * This method confirms a Connection.Close method and tells the recipient
     * that it is safe to release resources for the connection and close the
     * socket.
     */
    protected function sendConnectionCloseOk()
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
     * @param bool $active If true, the peer starts sending content frames.
     *                     If false, the peer stops sending content frames.
     */
    protected function sendChannelFlowOk(bool $active)
    {
        $active = $active ? 1 : 0;

        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($this->currentChannel);

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
     * This method confirms a Channel.Close method and tells the recipient that
     * it is safe to release resources for the channel.
     */
    protected function sendChannelCloseOk()
    {
        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($this->currentChannel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CHANNEL_CLASS_ID);
        $this->transmitter->sendShort(static::CHANNEL_CLOSE_OK);
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
        $this->transmitter->disableBuffering();
        $this->validateFrameDelimiter();

        $this->transmitter->enableBuffering();
        $replyCode = $this->transmitter->receiveShort();
        $replyMessage = $this->transmitter->receiveShortStr();
        $exceptionClassId = $this->transmitter->receiveShort();
        $exceptionMethodId = $this->transmitter->receiveShort();
        $this->transmitter->disableBuffering();

        $trailingMessage = $exceptionClassId && $exceptionMethodId
            ? "And exception class id '{$exceptionClassId}', exception method id '{$exceptionMethodId}'."
            : "";

        throw new SessionException(
            "Server closes the connection with reply code '{$replyCode}' and
                 message '{$replyMessage}'. ".$trailingMessage
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
        if(static::FRAME_DELIMITER != $this->transmitter->receiveRaw(1)){
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
        $this->transmitter->sendRaw(static::FRAME_DELIMITER);
    }
}
