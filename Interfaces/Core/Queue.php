<?php


namespace MonsterMQ\Interfaces\Core;


interface Queue
{
    /**
     * Declares queue.
     *
     * @return \MonsterMQ\Core\Queue
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function declare();

    /**
     * Bind queue to an exchange.
     *
     * @param string $exchangeName Exchange going to be bound.
     * @param string $routingKey   Routing key for binding.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function bind(string $exchangeName, string $routingKey);

    /**
     * Unbind a queue from an exchange.
     *
     * @param string $exchangeName Exchange to unbind.
     * @param string $routingKey   Routing key for unbinding.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function unbind(string $exchangeName, string $routingKey);

    /**
     * This method deletes messages from the queue.
     *
     * @return int Number of deleted messages.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function purge();

    /**
     * Deletes queue.
     *
     * @return int Number of deleted messages.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function delete();

    /**
     * Deletes queue only if no consumers of queue left.
     *
     * @return int Number of deleted messages.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function deleteIfUnused();

    /**
     * Deletes queue only if it is empty.
     *
     * @return int Number of deleted messages.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function deleteIfEmpty();

    /**
     * Sets name of the queue currently being declared.
     *
     * @param string $queueName Queue name currently being declared.
     */
    public function setCurrentQueueName(string $queueName);

    /**
     * Sets currently declaring queue durable.
     *
     * @return \MonsterMQ\Core\Queue
     */
    public function setDurable();

    /**
     * Sets currently declaring queue exclusive.
     *
     * @return \MonsterMQ\Core\Queue
     */
    public function setExclusive();

    /**
     * Sets currently declaring queue autodeleted.
     *
     * @return \MonsterMQ\Core\Queue
     */
    public function setAutodelete();
}