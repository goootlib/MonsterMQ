<?php

namespace MonsterMQ\AMQPDispatchers;

use MonsterMQ\Exceptions\ProtocolException;
use MonsterMQ\Interfaces\AMQPDispatchers\TransactionDispatcher as TransactionDispatcherInterface

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

    public function receiveSelectOk()
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if ($classId != self::TX_CLASS_ID || $methodId != self::TX_SELECT_OK) {
            throw new ProtocolException("Unexpected method frame. Expecting 
                class id '90' and method id '11'. '{$classId}' and '{$methodId}' given.");
        }

        $this->validateFrameDelimiter();
    }

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

    public function receiveCommitOk()
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if ($classId != self::TX_CLASS_ID || $methodId != self::TX_COMMIT_OK) {
            throw new ProtocolException("Unexpected method frame. Expecting 
                class id '90' and method id '21'. '{$classId}' and '{$methodId}' given.");
        }
    }

    public function sendRollback(int $channel)
    {

    }

    public function receiveRollbackOk()
    {

    }
}