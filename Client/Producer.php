<?php

namespace MonsterMQ\Client;

/**
 * This class provides API for interacting with AMQP server.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class Producer extends BaseClient
{
    public const CONTENT_HEADER_FRAME_TYPE = 2;
    public const CONTENT_BODY_FRAME_TYPE = 3;
    public const BASIC_CLASS_ID = 60;

    /**
     * Routing key that will be used on publishing without any routing key
     * specified
     *
     * @var string
     */
    protected $defaultRoutingKey;

    /**
     * Exchange that will be used if no exchange was specified on publishing.
     *
     * @var string
     */
    protected $defaultExchange = '';

    /**
     * Enqueues a message.
     *
     * @param string $message Message to be enqueued.
     */
    public function publish (string $message, $routingKey = null, $exchange = '')
    {
        $routingKey = is_null($routingKey) ? $this->defaultRoutingKey : $routingKey;
        $exchange = $exchange != '' ? $exchange : $this->defaultExchange;

        $this->basicDispatcher->sendPublish(
            $this->currentChannel(),
            $routingKey,
            $exchange,
            false,
            false
        );

        $length = $this->getMessageLength($message);
        $this->sendContentHeader($length);
        $this->sendContentBody($message);
    }

    /**
     * Returns length of the message.
     *
     * @param string $message Message which size is calculated.
     *
     * @return int Size of message.
     */
    protected function getMessageLength(string $message)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($message);
        } else {
            return strlen($message);
        }
    }

    /**
     * Sends content header frame that contains message metadata.
     *
     * @param int $messageLength Length of message to be sent.
     */
    protected function sendContentHeader(int $messageLength)
    {
        $this->transmitter->sendOctet(self::CONTENT_HEADER_FRAME_TYPE);
        $this->transmitter->sendShort($this->currentChannel());

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(self::BASIC_CLASS_ID);
        $this->transmitter->sendShort(0);
        $this->transmitter->sendLongLong($messageLength);
        //16 bits of message properties
        $this->transmitter->sendOctet(0);
        $this->transmitter->sendOctet(0);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->transmitter->sendRaw("\xCE");
    }

    /**
     * Sends whole message.
     *
     * @param string $message Message to send.
     */
    protected function sendContentBody(string $message)
    {
        $offset = 0;
        $limit = $this->session()->frameMaxSize() ?? 4096;
        if (function_exists('mb_substr')) {
            while ($offset < mb_strlen($message)) {
                $chunk = mb_substr($message, $offset, $limit);
                $offset += $limit;
                $this->sendContentBodyChunk($chunk);
            }
        } else {
            while ($offset < strlen($message)) {
                $chunk = substr($message, $offset, $limit);
                $offset += $limit;
                $this->sendContentBodyChunk($chunk);
            }
        }
    }

    /**
     * Sends part of message allowed for transmission.
     *
     * @param string $chunk Part of message allowed for transmission.
     */
    protected function sendContentBodyChunk(string $chunk)
    {
        $this->transmitter->sendOctet(self::CONTENT_BODY_FRAME_TYPE);
        $this->transmitter->sendShort($this->currentChannel());

        $this->transmitter->enableBuffering();
        $this->transmitter->sendRaw($chunk);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->transmitter->sendRaw("\xCE");
    }

    /**
     * Sets the default routing key for publishing.
     *
     * @param string $routingKey Default routing key.
     *
     * @return $this
     */
    public function defaultRoutingKey(string $routingKey)
    {
        $this->defaultRoutingKey = $routingKey;
        return $this;
    }

    /**
     * Sets the default exchange for publishing.
     *
     * @param string $exchange Default exchange.
     *
     * @return $this
     */
    public function overrideDefaultExchange(string $exchange)
    {
        $this->defaultExchange = $exchange;
        return $this;
    }
}