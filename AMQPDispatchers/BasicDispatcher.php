<?php

namespace MonsterMQ\AMQPDispatchers;

use MonsterMQ\Exceptions\ConnectionException;
use MonsterMQ\Exceptions\ProtocolException;
use MonsterMQ\Interfaces\AMQPDispatchers\BasicDispatcher as BasicDispatcherInterface;

/**
 * This dispatcher provides basic capabilities for interaction between client
 * and server.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class BasicDispatcher extends BaseDispatcher implements BasicDispatcherInterface
{
    /**
     * Delivery tag of last received message.
     *
     * @var int
     */
    protected $currentDeliveryTag;

    /**
     * This method requests a specific quality of service. The QoS can be
     * specified for the current channel or for all channels on the connection.
     *
     * @param int  $channel       Channel going to be used for setting up quality
     *                            of service.
     * @param int  $prefetchSize  Size of message which may be sent in advance.
     * @param int  $prefetchCount Number of messages which may be sent in advance.
     * @param bool $global        False enables qos per consumer. True - per channel.
     */
    public function sendQos(int $channel, int $prefetchSize = 0, int $prefetchCount = 0, bool $global = false)
    {
        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::BASIC_CLASS_ID);
        $this->transmitter->sendShort(static::BASIC_QOS);
        $this->transmitter->sendLong($prefetchSize);
        $this->transmitter->sendShort($prefetchCount);
        $global = $global ? 1 : 0;
        $this->transmitter->sendOctet($global);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }

    /**
     * This method tells the client that the requested QoS levels could be handled
     * by the server. The requested QoS applies to all active consumers until
     * a new QoS is defined.
     *
     * @throws ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     * @throws ConnectionException
     */
    public function receiveQosOk()
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if ($classId != static::BASIC_CLASS_ID || $methodId != static::BASIC_QOS_OK) {
            throw new ProtocolException("Unexpected method frame. Expecting 
                class id '60' and method id '11'. '{$classId}' and '{$methodId}' given.");
        }

        $this->validateFrameDelimiter();
    }

    /**
     * This method asks the server to start a "consumer", which is a transient
     * request for messages from a specific queue. Consumers last as long as
     * the channel they were declared on, or until the client cancels them.
     *
     * @param int    $channel     Channel which going to be used.
     * @param string $queueName   Queue to consume.
     * @param string $consumerTag The identifier of the consumer.
     * @param bool   $noLocal     If the no-local field is set the server will
     *                            not send messages to the connection that
     *                            published them.
     * @param bool   $noAck       If this field is set the server does not
     *                            expect acknowledgements for messages. That
     *                            is, when a message is delivered to the client
     *                            the server assumes the delivery will succeed
     *                            and immediately dequeues it.
     * @param bool   $exclusive   Request exclusive consumer access, meaning
     *                            only this consumer can access the queue.
     * @param bool   $noWait      Whether the server will respond with Consume-Ok
     * @param array  $arguments   A set of arguments for the consume. The syntax
     *                            and semantics of these arguments depends on the
     *                            server implementation.
     */
    public function sendConsume(
        int $channel,
        string $queueName,
        string $consumerTag = '',
        bool $noLocal = false,
        bool $noAck = false,
        bool $exclusive = false,
        bool $noWait = false,
        array $arguments = []
    ) {
        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::BASIC_CLASS_ID);
        $this->transmitter->sendShort(static::BASIC_CONSUME);
        //Following field reserved by AMQP
        $this->transmitter->sendShort(0);
        $this->transmitter->sendShortStr($queueName);
        $this->transmitter->sendShortStr($consumerTag);
        $noLocal = $noLocal ? 1 : 0;
        $noAck = $noAck ? 1 : 0;
        $exclusive = $exclusive ? 1 : 0;
        $noWait = $noWait ? 1 : 0;
        $this->transmitter->sendSeveralBits([$noLocal, $noAck, $exclusive, $noWait]);
        $this->transmitter->sendFieldTable($arguments);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }

    /**
     * Confirm a new consumer.
     *
     * @return string $consumerTag The identifier of consumer which may be
     *                             specified by client or generated by server.
     *
     * @throws ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     * @throws ConnectionException
     */
    public function receiveConsumeOk()
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if ($classId != static::BASIC_CLASS_ID || $methodId != static::BASIC_CONSUME_OK) {
            throw new ProtocolException("Unexpected method frame. Expecting 
                class id '60' and method id '21'. '{$classId}' and '{$methodId}' given.");
        }

        $consumerTag = $this->transmitter->receiveShortStr();
        $this->validateFrameDelimiter();

        return $consumerTag;
    }

    /**
     * End a queue consumer.
     *
     * @param int    $channel     Channel going to be used.
     * @param string $consumerTag Consumer tag that indicates which consumer
     *                            will be ended.
     * @param bool   $noWait      Whether the server will respond with Cancel-Ok.
     */
    public function sendCancel(int $channel, string $consumerTag, bool $noWait = false)
    {
        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::BASIC_CLASS_ID);
        $this->transmitter->sendShort(static::BASIC_CANCEL);
        $this->transmitter->sendShortStr($consumerTag);
        $noWait = $noWait ? 1 : 0;
        $this->transmitter->sendOctet($noWait);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }

    /**
     * This method confirms that the cancellation was completed.
     *
     * @return string $consumerTag Identifier of consumer which was canceled.
     *
     * @throws ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     * @throws ConnectionException
     */
    public function receiveCancelOk()
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if ($classId != static::BASIC_CLASS_ID || $methodId != static::BASIC_CANCEL_OK) {
            throw new ProtocolException("Unexpected method frame. Expecting 
                class id '60' and method id '31'. '{$classId}' and '{$methodId}' given.");
        }

        $consumerTag = $this->transmitter->receiveShortStr();
        $this->validateFrameDelimiter();

        return $consumerTag;
    }

    /**
     * This method publishes a message to a specific exchange. The message will
     * be routed to queues as defined by the exchange configuration and
     * distributed to any active consumers when the transaction, if any, is
     * committed.
     *
     * @param int    $channel      Channel going to be used.
     * @param string $routingKey   Routing key for publishing.
     * @param string $exchangeName Exchange going to be used for publishing.
     * @param bool   $mandatory    This flag tells the server how to react if
     *                             the message cannot be routed to a queue. If
     *                             this flag is set, the server will return an
     *                             unroutable message with a Return method. If
     *                             this flag is zero, the server silently drops
     *                             the message.
     * @param bool   $immediate    This flag tells the server how to react if
     *                             the message cannot be routed to a queue
     *                             consumer immediately. If this flag is set,
     *                             the server will return an undeliverable
     *                             message with a Return method. If this flag
     *                             is zero, the server will queue the message,
     *                             but with no guarantee that it will ever be
     *                             consumed.
     */
    public function sendPublish(
        int $channel,
        string $routingKey,
        string $exchangeName = "",
        bool $mandatory = false,
        bool $immediate = false
    ) {
        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::BASIC_CLASS_ID);
        $this->transmitter->sendShort(static::BASIC_PUBLISH);
        //Following argument reserved by AMQP
        $this->transmitter->sendShort(0);
        $this->transmitter->sendShortStr($exchangeName);
        $this->transmitter->sendShortStr($routingKey);
        $mandatory = $mandatory ? 1 : 0;
        $immediate = $immediate ? 1 : 0;
        $this->transmitter->sendSeveralBits([$mandatory, $immediate]);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }

    /**
     * This method returns an undeliverable message that was published with the
     * "immediate" flag set, or an unroutable message published with the
     * "mandatory" flag set. The reply code and text provide information
     * about the reason that the message was undeliverable.
     *
     * @return array Contains reply code, reply text, exchange and routing key
     *               that was used for publishing.
     *
     * @throws ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     * @throws ConnectionException
     */
    public function receiveReturn()
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if ((!is_null($classId) && $classId != static::BASIC_CLASS_ID) || (!is_null($methodId) && $methodId != static::BASIC_RETURN)) {
            throw new ProtocolException("Unexpected method frame. Expecting 
                class id '60' and method id '50'. '{$classId}' and '{$methodId}' given.");
        }

        $replyCode = $this->transmitter->receiveShort();
        $replyText = $this->transmitter->receiveShortStr();
        $exchange = $this->transmitter->receiveShortStr();
        $routingKey = $this->transmitter->receiveShortStr();

        $this->validateFrameDelimiter();

        return [$replyCode, $replyText, $exchange, $routingKey];
    }

    /**
     * Receives Deliver method frame along with content message from the server.
     *
     * @return array First element is content of incoming message,
     *               second - the channel was used.
     *
     * @throws ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     * @throws ConnectionException
     */
    public function receiveMessage()
    {
        $this->receiveDeliver();
        return $this->receiveContent();
    }

    /**
     * Receives Deliver method frame from the server.
     *
     * @throws ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     * @throws ConnectionException
     */
    protected function receiveDeliver()
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if ($classId == static::BASIC_CLASS_ID && $methodId == static::BASIC_DELIVER) {
            $consumerTag = $this->transmitter->receiveShortStr();
            $this->setCurrentDeliveryTag($this->transmitter->receiveLongLong());
            $redelivered = $this->transmitter->receiveOctet();
            $exchange = $this->transmitter->receiveShortStr();
            $routingKey = $this->transmitter->receiveShortStr();

            $this->validateFrameDelimiter();
        }
    }

    /**
     * Stores delivery tag of currently incoming message.
     *
     * @param int $deliveryTag
     */
    protected function setCurrentDeliveryTag(int $deliveryTag)
    {
        $this->currentDeliveryTag = $deliveryTag;
    }

    /**
     * Returns current delivery tag.
     *
     * @return int Current delivery tag.
     */
    protected function currentDeliveryTag()
    {
        return $this->currentDeliveryTag;
    }

    /**
     * Method for receiving incoming content messages.
     *
     * @return array Returns content of the incoming message and the channel
     *               which was used.
     *
     * @throws ProtocolException
     * @throws ConnectionException
     */
    protected function receiveContent()
    {
        [$usedChannel, $bodySize] = $this->receiveContentHeader();
        $content = $this->receiveContentBody($bodySize);
        return [$content, $usedChannel];
    }

    /**
     * Receives content header frame.
     *
     * @return array Returns channel that was used and content body size.
     *
     * @throws ProtocolException
     * @throws ConnectionException
     */
    protected function receiveContentHeader()
    {
        $frameType = $this->receiveFrameType();
        if ($frameType != static::CONTENT_HEADER_FRAME_TYPE) {
            throw new ProtocolException(
                'Unexpected frame type. 2 was expected. '.$frameType.' was given.'
            );
        }
        $this->setCurrentChannel($usedChannel = $this->transmitter->receiveShort());
        $this->transmitter->receiveLong();

        $classId = $this->transmitter->receiveShort();
        $this->transmitter->receiveShort();
        $bodySize = $this->transmitter->receiveLongLong();
        //Receive two bytes of properties
        $this->transmitter->receiveOctet();
        $this->transmitter->receiveOctet();

        $this->validateFrameDelimiter();

        return [$usedChannel, $bodySize];
    }

    /**
     * Receives content body frame.
     *
     * @param $bodySize Message size.
     *
     * @return string Message.
     *
     * @throws ProtocolException
     * @throws ConnectionException
     */
    public function receiveContentBody($bodySize)
    {
        $read = 0;

        while ($read < $bodySize) {
            $frameType = $this->receiveFrameType();
            if ($frameType != static::CONTENT_BODY_FRAME_TYPE) {
                throw new ProtocolException(
                    'Unexpected frame type. 3 was expected. '.$frameType.' was given.'
                );
            }
            $this->setCurrentChannel($this->transmitter->receiveShort());
            $this->setCurrentFrameSize($this->transmitter->receiveLong());
            $chunk = $this->transmitter->receiveRaw($this->currentFrameSize());
            $read += strlen($chunk);
            $content[] = $chunk;
            $this->validateFrameDelimiter();
        }

        return implode('', $content);
    }

    /**
     * This method provides a direct access to the messages in a queue using a
     * synchronous dialogue that is designed for specific types of application
     * where synchronous functionality is more important than performance.
     *
     * @param int    $channel   Channel going to be used.
     * @param string $queueName Queue to be accessed.
     * @param bool   $noAck     If this field is set the server does not
     *                          expect acknowledgements for messages. That
     *                          is, when a message is delivered to the client
     *                          the server assumes the delivery will succeed
     *                          and immediately dequeues it.
     */
    public function sendGet(int $channel, string $queueName, bool $noAck = false)
    {
        $this->transmitter->sendOctet(self::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(self::BASIC_CLASS_ID);
        $this->transmitter->sendShort(self::BASIC_GET);
        //Following argument reserved by AMQP
        $this->transmitter->sendShort(0);
        $this->transmitter->sendShortStr($queueName);
        $noAck = $noAck ? 1 : 0;
        $this->transmitter->sendOctet($noAck);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }

    /**
     * Receives response to Get method along with following message.
     *
     * @return array|null Message content with used channel. Or null if requested
     *                    queue is empty.
     *
     * @throws ConnectionException
     * @throws ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function receiveGetOkOrGetEmpty()
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if ($classId != self::BASIC_CLASS_ID || ($methodId != self::BASIC_GET_OK || $methodId != self::BASIC_GET_EMPTY)) {
            throw new ProtocolException(
                "Unexpected method frame. Expecting 
                class id '60' and method id '71' or '72'. '{$classId}' and '{$methodId}' given.");
        }

        if ($methodId == self::BASIC_GET_EMPTY) {
            $this->transmitter->receiveShortStr();
            $this->validateFrameDelimiter();
            return false;
        } else {
            $this->setCurrentDeliveryTag($this->transmitter->receiveLongLong());
            $redelivered = $this->transmitter->receiveOctet();
            $exchangeName = $this->transmitter->receiveShortStr();
            $routingKey = $this->transmitter->receiveShortStr();
            $messageCount = $this->transmitter->receiveLong();
            $this->validateFrameDelimiter();
            return $this->receiveContent();
        }
    }

    /**
     * Acknowledges one or more messages.
     *
     * @param int  $channel  Channel going to be used.
     * @param bool $multiple If set to 1, the delivery tag is treated as
     *                       "up to and including", so that multiple messages
     *                        can be acknowledged with a single method. If set
     *                        to zero, the delivery tag refers to a single
     *                        message. If the multiple field is 1, and the
     *                        delivery tag is zero, this indicates acknowledgement
     *                        of all outstanding messages.
     */
    public function sendAck(int $channel, $deliveryTag = null, bool $multiple = false)
    {
        $this->transmitter->sendOctet(self::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(self::BASIC_CLASS_ID);
        $this->transmitter->sendShort(self::BASIC_ACK);
        $deliveryTag = $deliveryTag ?? $this->currentDeliveryTag();
        $this->transmitter->sendLongLong($deliveryTag);
        $multiple = $multiple ? 1 : 0;
        $this->transmitter->sendOctet($multiple);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }

    /**
     * This method allows a client to reject a message. It can be used to
     * interrupt and cancel large incoming messages, or return untreatable
     * messages to their original queue.
     *
     * @param int  $channel Channel going to be used.
     * @param bool $requeue If requeue is true, the server will attempt to
     *                       requeue the message. If requeue is false or the
     *                       requeue attempt fails the messages are discarded
     *                       or dead-lettered.
     */
    public function sendReject(int $channel, bool $requeue = false)
    {
        $this->transmitter->sendOctet(self::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(self::BASIC_CLASS_ID);
        $this->transmitter->sendShort(self::BASIC_REJECT);
        $this->transmitter->sendLongLong($this->currentDeliveryTag());
        $requeue = $requeue ? 1 : 0;
        $this->transmitter->sendOctet($requeue);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }

    /**
     * This method asks the server to redeliver all unacknowledged messages
     * on a specified channel. Zero or more messages may be redelivered.
     *
     * @param int $channel  Channel going to be used.
     * @param bool $requeue If this field is zero, the message will be redelivered
     *                      to the original recipient. If this bit is 1, the server
     *                      will attempt to requeue the message, potentially then
     *                      delivering it to an alternative subscriber.
     */
    public function sendRecover(int $channel, bool $requeue = false)
    {
        $this->transmitter->sendOctet(self::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(self::BASIC_CLASS_ID);
        $this->transmitter->sendShort(self::BASIC_RECOVER);
        $requeue = $requeue ? 1 : 0;
        $this->transmitter->sendOctet($requeue);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }

    /**
     * Confirms recovery.
     *
     * @throws ConnectionException
     * @throws ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function receiveRecoverOk()
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if ($classId != self::BASIC_CLASS_ID || $methodId != self::BASIC_RECOVER_OK) {
            throw new ProtocolException("Unexpected method frame. Expecting 
                class id '60' and method id '111'. '{$classId}' and '{$methodId}' given.");
        }

        $this->validateFrameDelimiter();
    }

    /**
     * This method allows a client to reject one or more incoming messages. It
     * can be used to interrupt and cancel large incoming messages, or return
     * untreatable messages to their original queue.
     *
     * @param int  $channel     Channel going to be used.
     * @param null $deliveryTag Delivery tag of message.
     * @param bool $multiple    If set to 1, the delivery tag is treated as
     *                          "up to and including", so that multiple messages
     *                          can be rejected with a single method. If set to
     *                          zero, the delivery tag refers to a single message.
     *                          If the multiple field is 1, and the delivery tag
     *                          is zero, this indicates rejection of all
     *                          outstanding messages.
     * @param bool $requeue     If requeue is true, the server will attempt to
     *                          requeue the message. If requeue is false or the
     *                          requeue attempt fails the messages are discarded
     *                          or dead-lettered.
     */
    public function sendNack(int $channel, $deliveryTag = null, bool $multiple = false, bool $requeue = false)
    {
        $this->transmitter->sendOctet(self::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(self::BASIC_CLASS_ID);
        $this->transmitter->sendShort(self::BASIC_NACK);
        $deliveryTag = $deliveryTag ?? $this->currentDeliveryTag();
        $this->transmitter->sendLongLong($deliveryTag);
        $multiple = $multiple ? 1 : 0;
        $requeue = $requeue ? 1 : 0;
        $this->transmitter->sendSeveralBits([$multiple, $requeue]);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }
}