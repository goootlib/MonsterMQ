<?php

namespace MonsterMQ\Core;

use MonsterMQ\Client\BaseClient;
use MonsterMQ\Interfaces\AMQPDispatchers\BasicDispatcher as BasicDispatcherInterface;
use MonsterMQ\Interfaces\Core\Qos as QosInterface;
use MonsterMQ\Interfaces\Support\Logger as LoggerInterface;

/**
 * This class provides API for end-users to adjust quality of service.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class Qos implements QosInterface
{
    /**
     * Instance of basic dispatcher.
     *
     * @var BasicDispatcherInterface
     */
    protected $basicDispatcher;

    /**
     * Client instance.
     *
     * @var BaseClient
     */
    protected $client;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Size of message which may be sent in advance.
     *
     * @var int
     */
    protected $prefetchSize = 0;

    /**
     * Number of messages which may be sent in advance.
     *
     * @var int
     */
    protected $prefetchCount = 0;

    /**
     * False enables qos per consumer. True - per channel.
     *
     * @var bool
     */
    protected $global = false;

    /**
     * Qos constructor.
     *
     * @param BasicDispatcherInterface $basicDispatcher
     */
    public function __construct(BasicDispatcherInterface $basicDispatcher, BaseClient $client, LoggerInterface $logger)
    {
        $this->basicDispatcher = $basicDispatcher;
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * Sets number of messages which may be sent in advance.
     *
     * @param int $count
     *
     * @return $this
     */
    public function prefetchCount(int $count = 0)
    {
        $this->prefetchCount = $count;

        return $this;
    }

    /**
     * Enables qos per consumer.
     *
     * @return $this
     */
    public function perConsumer()
    {
        $this->global = false;

        return $this;
    }

    /**
     * Enables qos per channel.
     *
     * @return $this
     */
    public function perChannel()
    {
        $this->global = true;

        return $this;
    }

    /**
     * Applies qos settings.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function apply()
    {
        $channel = $this->client->currentChannel();
        $global = $this->global ? "per channel" : "per consumer";
        $this->logger->write("Appling quality of service prefetch count {$this->prefetchCount} {$global} on channel {$channel}");

        $this->basicDispatcher->sendQos(
            $this->client->currentChannel(),
            $this->prefetchSize,
            $this->prefetchCount,
            $this->global
        );
        $this->basicDispatcher->receiveQosOk();
    }
}