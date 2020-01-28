<?php

namespace MonsterMQ\Core;

use MonsterMQ\Client\BaseClient;
use MonsterMQ\Interfaces\AMQPDispatchers\TransactionDispatcher as TransactionDispatcherInterface;
use MonsterMQ\Interfaces\Support\Logger as LoggerInterface;
use MonsterMQ\Interfaces\Core\Transaction as TransactionInterface;

/**
 * This class provides transaction API for end-users. AMQP transactions
 * allow to batch publish and ack operations into single atomic units. The
 * intention is that all publish and ack requests issued within a transaction
 * will complete successfully or none of them will.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class Transaction implements TransactionInterface
{
    /**
     * Transaction dispatcher instance.
     *
     * @var TransactionDispatcherInterface
     */
    protected $transactionDispatcher;

    /**
     * Client instance.
     *
     * @var BaseClient
     */
    protected $client;

    /**
     * Logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Transaction constructor.
     *
     * @param TransactionDispatcherInterface $transactionDispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(TransactionDispatcherInterface $transactionDispatcher, BaseClient $client, LoggerInterface $logger)
    {
        $this->transactionDispatcher = $transactionDispatcher;
        $this->logger = $logger;
        $this->client = $client;
    }

    /**
     * Sets the transaction mode on current channel.
     */
    public function begin()
    {
        $channel = $this->client->currentChannel();
        $this->logger->write("Set transaction mode on channel {$channel}");

        $this->transactionDispatcher->sendSelect($this->client->currentChannel());
        $this->transactionDispatcher->receiveSelectOk();
    }

    /**
     * Commit current transaction. A new transaction starts immediately after
     * a commit.
     */
    public function commit()
    {
        $channel = $this->client->currentChannel();
        $this->logger->write("Commit transaction on channel {$channel}");

        $this->transactionDispatcher->sendCommit($this->client->currentChannel());
        $this->transactionDispatcher->receiveCommitOk();
    }

    /**
     * Abandons current transaction. A new transaction starts immediately after
     * a rollback.
     */
    public function rollback()
    {
        $channel = $this->client->currentChannel();
        $this->logger->write("Rollback transaction on channel {$channel}");

        $this->transactionDispatcher->sendRollback($this->client->currentChannel());
        $this->transactionDispatcher->receiveRollbackOk();
    }
}