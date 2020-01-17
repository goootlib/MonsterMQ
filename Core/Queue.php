<?php


namespace MonsterMQ\Core;

use MonsterMQ\Client\BaseClient;
use MonsterMQ\Interfaces\AMQPDispatchers\QueueDispatcher as QueueDispatcherInterface;
use MonsterMQ\Interfaces\Core\Queue as QueueInterface;
use MonsterMQ\Interfaces\Support\Logger as LoggerInterface;

/**
 * This class provides API for queue management for end-users.
 *
 *@author Gleb Zhukov <goootlib@gmail.com>
 */
class Queue implements QueueInterface
{
    /**
     * Queue dispatcher instance.
     *
     * @var QueueDispatcherInterface
     */
    protected $queueDispatcher;

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
     * Current queue name.
     *
     * @var string
     */
    protected $currentQueueName = '';

    /**
     * Whether declaring queue going to be durable. Durable queues remain active
     * when a server restarts. Non-durable queues (transient queues) are purged
     * if/when a server restarts.
     *
     * @var bool
     */
    protected $durable = false;

    /**
     * Whether declaring queue going to be exclusive. Exclusive queues may only
     * be accessed by the current connection, and are deleted when that
     * connection closes.
     *
     * @var bool
     */
    protected $exclusive = false;

    /**
     * Whether declaring queue going to be autodeleted. Autodeleted queues
     * deletes when no consumers left.
     *
     * @var
     */
    protected $autodelete = false;

    /**
     * Queue constructor.
     *
     * @param QueueDispatcherInterface $dispatcher
     * @param BaseClient $client
     */
    public function __construct(QueueDispatcherInterface $dispatcher, BaseClient $client, LoggerInterface $logger)
    {
        $this->queueDispatcher = $dispatcher;
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * Declares queue.
     *
     * @return $this
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function declare()
    {
        $durable = $this->durable ? "durable" : '';
        $exclusive = $this->exclusive ? 'exclusive' : '';
        $autodelete = $this->autodelete ? "autodelete" : '';
        $channel = $this->client->currentChannel();
        $this->logger->write("Declaring {$exclusive} {$durable} {$autodelete} queue with name '{$this->currentQueueName}' on channel {$channel}");

        $this->queueDispatcher->sendDeclare(
            $this->client->currentChannel(),
            $this->currentQueueName,
            false,
            $this->durable,
            $this->exclusive,
            $this->autodelete
        );

        [$queueName, $messageCount, $consumerCount] = $this->queueDispatcher->receiveDeclareOk();

        $this->flushArguments();

        return $this;
    }

    /**
     * Bind queue to an exchange.
     *
     * @param string $exchangeName Exchange going to be bound.
     * @param string $routingKey   Routing key for binding.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function bind(string $exchangeName, string $routingKey)
    {
        $channel = $this->client->currentChannel();
        $this->logger->write("Binding queue '{$this->currentQueueName}' to exchange '{$exchangeName}' with routing key '{$routingKey}' on channel {$channel}");

        $this->queueDispatcher->sendBind(
            $this->client->currentChannel(),
            $this->currentQueueName,
            $exchangeName,
            $routingKey
        );

        $this->queueDispatcher->receiveBindOk();
    }

    /**
     * Unbind a queue from an exchange.
     *
     * @param string $exchangeName Exchange to unbind.
     * @param string $routingKey   Routing key for unbinding.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function unbind(string $exchangeName, string $routingKey)
    {
        $channel = $this->client->currentChannel();
        $this->logger->write("Unbinding queue with name '{$this->currentQueueName}' from exchange with name '{$exchangeName}' and routing key '{$routingKey}' on channel {$channel}");

        $this->queueDispatcher->sendUnbind(
            $this->client->currentChannel(),
            $this->currentQueueName,
            $exchangeName,
            $routingKey
        );

        $this->queueDispatcher->receiveUnbindOk();
    }

    /**
     * This method deletes messages from the queue.
     *
     * @return int Number of deleted messages.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function purge()
    {
        $channel = $this->client->currentChannel();
        $this->logger->write("Purging queue with name '{$this->currentQueueName}' on channel {$channel}");

        $this->queueDispatcher->sendPurge(
            $this->client->currentChannel(),
            $this->currentQueueName
        );

        $deletedMessages = $this->queueDispatcher->receivePurgeOk();

        return $deletedMessages;
    }

    /**
     * Deletes queue.
     *
     * @return int Number of deleted messages.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function delete()
    {
        $channel = $this->client->currentChannel();
        $this->logger->write("Deleting queue '{$this->currentQueueName}' on channel {$channel}");

        $this->queueDispatcher->sendDelete(
            $this->client->currentChannel(),
            $this->currentQueueName
        );

        $deletedMessages = $this->queueDispatcher->receiveDeleteOk();

        return $deletedMessages;
    }

    /**
     * Deletes queue only if no consumers of queue left.
     *
     * @return int Number of deleted messages.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function deleteIfUnused()
    {
        $channel = $this->client->currentChannel();
        $this->logger->write("Deleting unused (no consumers) queue with name '{$this->currentQueueName}' on channel {$channel}");

        $this->queueDispatcher->sendDelete(
            $this->client->currentChannel(),
            $this->currentQueueName,
            true
        );

        $deletedMessages = $this->queueDispatcher->receiveDeleteOk();

        return $deletedMessages;
    }

    /**
     * Deletes queue only if it is empty.
     *
     * @return int Number of deleted messages.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function deleteIfEmpty()
    {
        $channel = $this->client->currentChannel();
        $this->logger->write("Deleting empty queue with name '{$this->currentQueueName}' on channel {$channel}");

        $this->queueDispatcher->sendDelete(
            $this->client->currentChannel(),
            $this->currentQueueName,
            false,
            true
        );

        $deletedMessages = $this->queueDispatcher->receiveDeleteOk();

        return $deletedMessages;
    }

    /**
     * Flushes all queue declaration arguments.
     */
    protected function flushArguments()
    {
        $this->durable = false;
        $this->exclusive = false;
        $this->autodelete = false;
    }

    /**
     * Sets name of the queue currently being declared.
     *
     * @param string $queueName Queue name currently being declared.
     */
    public function setCurrentQueueName(string $queueName)
    {
        $this->currentQueueName = $queueName;
    }

    /**
     * Sets currently declaring queue durable.
     *
     * @return $this
     */
    public function setDurable()
    {
        $this->durable = true;

        return $this;
    }

    /**
     * Sets currently declaring queue exclusive.
     *
     * @return $this
     */
    public function setExclusive()
    {
        $this->exclusive = true;

        return $this;
    }

    /**
     * Sets currently declaring queue autodeleted.
     *
     * @return $this
     */
    public function setAutodelete()
    {
        $this->autodelete = true;

        return $this;
    }
}