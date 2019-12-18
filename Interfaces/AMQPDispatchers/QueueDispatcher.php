<?php

namespace MonsterMQ\Interfaces\AMQPDispatchers;

interface QueueDispatcher extends AMQP
{
    /**
     * Declare queue, create if needed.
     *
     * @param int    $channel    Channel going to be used.
     * @param string $queueName  Name of queue to be declared.
     * @param bool   $passive    If set, the server will reply with Declare-Ok
     *                           if the queue already exists with the same name,
     *                           and raise an error if not.
     * @param bool   $durable    Durable queues remain active when a server
     *                           restarts. Non-durable queues (transient queues)
     *                           are purged if/when a server restarts.
     * @param bool   $exclusive  Exclusive queues may only be accessed by the
     *                           current connection, and are deleted when that
     *                           connection closes.
     * @param bool   $autodelete If set, the queue is deleted when all consumers
     *                           have finished using it.
     * @param bool   $noWait     Whether server will respond with Declare-Ok.
     * @param array  $arguments  A set of arguments for the declaration. The
     *                           syntax and semantics of these arguments
     *                           depends on the server implementation.
     */
    public function sendDeclare(
        int $channel,
        string $queueName = '',
        bool $passive = false,
        bool $durable = false,
        bool $exclusive = false,
        bool $autodelete = false,
        bool $noWait = false,
        array $arguments = []
    );

    /**
     * This method confirms a Declare method and confirms the name of the queue,
     * essential for automatically-named queues.
     *
     * @return array Returning array contains queue name, number of messages
     *               that queue contain and number of queue consumers.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function receiveDeclareOk();

    /**
     * Bind queue to an exchange.
     *
     * @param int    $channel      Channel going to be used.
     * @param string $queueName    Queue going to be bound.
     * @param string $exchangeName Exchange going to be bound.
     * @param string $routingKey   Routing key for binding.
     * @param bool   $noWait       Whether server will respond with Bind-Ok
     * @param array  $arguments    A set of arguments for the binding. The
     *                             syntax and semantics of these arguments
     *                             depends on the exchange class.
     */
    public function sendBind(
        int $channel,
        string $queueName,
        string $exchangeName,
        string $routingKey,
        bool $noWait = false,
        array $arguments = []
    );

    /**
     * This method confirms that the bind was successful.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function receiveBindOk();

    /**
     * Unbind a queue from an exchange.
     *
     * @param int    $channel      Channel going to be used for unbinding.
     * @param string $queueName    Queue to unbind.
     * @param string $exchangeName Exchange to unbind.
     * @param string $routingKey   Routing key to unbind.
     * @param array  $arguments    Specifies the arguments of the binding.
     */
    public function sendUnbind(
        int $channel,
        string $queueName,
        string $exchangeName,
        string $routingKey,
        array $arguments = []
    );

    /**
     * This method confirms that the unbind was successful.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function receiveUnbindOk();

    /**
     * This method removes all messages from a queue which are not awaiting
     * acknowledgment.
     *
     * @param int    $channel   Channel going to be used.
     * @param string $queueName Queue to purge.
     * @param bool   $noWait    Whether server will respond with Purge-Ok.
     */
    public function sendPurge(int $channel, string $queueName, $noWait = false);

    /**
     *This method confirms the purge of a queue.
     *
     * @return int $deletedMessages Number of messages that was deleted.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function receivePurgeOk();

    /**
     * This method deletes a queue. When a queue is deleted any pending messages
     * are sent to a dead-letter queue if this is defined in the server
     * configuration, and all consumers on the queue are cancelled.
     *
     * @param int    $channel   Channel going to be used.
     * @param string $queueName Queue which will be deleted.
     * @param bool   $unused    If set, the server will only delete the queue
     *                          if it has no consumers.
     * @param bool   $empty     If set, the server will only delete the queue
     *                          if it has no messages.
     * @param bool   $noWait    Whether server will respond with Delete-Ok.
     */
    public function sendDelete(
        int $channel,
        string $queueName,
        bool $unused = false,
        bool $empty = false,
        bool $noWait = false
    );

    /**
     * Confirms deletion of a queue.
     *
     * @return int $deletedMessages Number of messages that was deleted.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function receiveDeleteOk();
}