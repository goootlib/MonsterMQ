<?php


namespace MonsterMQ\AMQPDispatchers\Support;

use MonsterMQ\Exceptions\ConnectionException;
use MonsterMQ\Exceptions\SessionException;

/**
 * This trait responsible for handling closures.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
trait UnexpectedHandler
{
    /**
     * Timeout after which session and socket connection will be closed
     * without waiting for incoming close-ok method.
     *
     * @var int
     */
    protected $closeOkTimeout = 130;

    /**
     * Current frame size used by termination methods.
     *
     * @var int
     */
    protected $currentFrameSize;

    /**
     * Sets timeout after which connection will be closed without waiting for
     * incoming close-ok method.
     *
     * @param int $timeout
     */
    public function setCloseOkTimeout(int $timeout)
    {
        $this->closeOkTimeout = $timeout;
    }

    /**
     * Stores current frame size.
     *
     * @param int $size
     */
    public function setCurrentFrameSize(int $size)
    {
        $this->currentFrameSize = $size;
    }

    /**
     * Handles Connection.Close method if it comes instead of expecting
     * method. Otherwise returns AMQP class id and method id as associative
     * array.
     *
     * @return array First element AMQP class id, second element AMQP method id
     *
     * @throws SessionException
     */
    protected function receiveClassAndMethod(): array
    {
        $classId = $this->transmitter->receiveShort();
        $methodId = $this->transmitter->receiveShort();
        if ($classId == static::CLASS_ID && $methodId == static::CONNECTION_CLOSE) {
            $this->close();
        } else {
            return [$classId,$methodId];
        }
    }

    protected function waitCloseOK()
    {
        $start = time();
        do {
            if (time() == $start + $this->closeOkTimeout) {
                throw new ConnectionException(
                    "close-ok method expectation was timed out. Connection was closed."
                );
            }
            $this->transmitter->receiveOctet();
            $this->transmitter->receiveShort();
            $this->transmitter->receiveLong();
            [$classId, $methodId] = $this->receiveClassAndMethod();

            if ($classId == static::CLASS_ID && $methodId == static::CONNECTION_CLOSE_OK) {
                $this->socket->close();
            }
        } while($classId != static::CLASS_ID && $methodId != static::CONNECTION_CLOSE_OK);
    }

    protected function close()
    {
        $this->transmitter->enableBuffering();
        //Subtract 2 bytes as AMQP class id and yet 2 bytes as AMQP method id
        $this->transmitter->receiveIntoBuffer($this->currentFrameSize - 4);
        $this->validateFrameDelimiter();

        $replyCode = $this->transmitter->receiveShort();
        $replyMessage = $this->transmitter->receiveShortStr();
        $exceptionClassId = $this->transmitter->receiveShort();
        $exceptionMethodId = $this->transmitter->receiveShort();
        $this->transmitter->disableBuffering();

        $this->send_close_ok();

        $msg = $exceptionClassId && $exceptionMethodId
            ? "And exception class id '{$exceptionClassId}', exception method id '{$methodId}'."
            : "";

        throw new SessionException(
            "Server closes the connection with reply code '{$replyCode}' and
                 message '{$replyMessage}'. ".$msg
        );
    }
}