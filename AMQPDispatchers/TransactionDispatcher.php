<?php

namespace MonsterMQ\AMQPDispatchers;

use MonsterMQ\Exceptions\ProtocolException;
use MonsterMQ\Interfaces\AMQPDispatchers\TransactionDispatcher as TransactionDispatcherInterface;

/**
 * This class provides means for utilizing AMQP transactions. AMQP transactions
 * allow to batch publish and ack operations into single atomic units. The
 * intention is that all publish and ack requests issued within a transaction
 * will complete successfully or none of them will.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class TransactionDispatcher extends BasicDispatcher implements TransactionDispatcherInterface
{
    /**
     * This method sets the channel to use standard transactions. The client
     * must use this method at least once on a channel before using the Commit
     * or Rollback methods.
     *
     * @param int $channel Channel which will be used for transactions.
     */
    public function sendSelect(int $channel)
    {
        $this->transmitter->sendOctet(self::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(self::TX_CLASS_ID);
        $this->transmitter->sendShort(self::TX_SELECT);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }

    /**
     * This method confirms to the client that the channel was successfully
     * set to use standard transactions.
     *
     * @throws ProtocolException
     * @throws \MonsterMQ\Exceptions\ConnectionException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function receiveSelectOk()
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if ($classId != self::TX_CLASS_ID || $methodId != self::TX_SELECT_OK) {
            throw new ProtocolException("Unexpected method frame. Expecting 
                class id '90' and method id '11'. '{$classId}' and '{$methodId}' given.");
        }

        $this->validateFrameDelimiter();
    }

    /**
     * This method commits all message publications and acknowledgments performed
     * in the current transaction. A new transaction starts immediately after
     * a commit.
     *
     * @param int $channel Channel that using for transactions.
     */
    public function sendCommit(int $channel)
    {
        $this->transmitter->sendOctet(self::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(self::TX_CLASS_ID);
        $this->transmitter->sendShort(self::TX_COMMIT);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }

    /**
     * This method confirms to the client that the commit succeeded. Note that
     * if a commit fails, the server raises a channel exception.
     *
     * @throws ProtocolException
     * @throws \MonsterMQ\Exceptions\ConnectionException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function receiveCommitOk()
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if ($classId != self::TX_CLASS_ID || $methodId != self::TX_COMMIT_OK) {
            throw new ProtocolException("Unexpected method frame. Expecting 
                class id '90' and method id '21'. '{$classId}' and '{$methodId}' given.");
        }

        $this->validateFrameDelimiter();
    }

    /**
     * This method abandons all message publications and acknowledgments
     * performed in the current transaction. A new transaction starts
     * immediately after a rollback.
     *
     * @param int $channel Channel that using for transactions.
     */
    public function sendRollback(int $channel)
    {
        $this->transmitter->sendOctet(self::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort($channel);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(self::TX_CLASS_ID);
        $this->transmitter->sendShort(self::TX_ROLLBACK);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }

    /**
     * This method confirms to the client that the rollback succeeded. Note
     * that if an rollback fails, the server raises a channel exception.
     *
     * @throws ProtocolException
     * @throws \MonsterMQ\Exceptions\ConnectionException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function receiveRollbackOk()
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if ($classId != self::TX_CLASS_ID || $methodId != self::TX_ROLLBACK_OK) {
            throw new ProtocolException("Unexpected method frame. Expecting 
                class id '90' and method id '31'. '{$classId}' and '{$methodId}' given.");
        }

        $this->validateFrameDelimiter();
    }
}