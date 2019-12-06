<?php


namespace MonsterMQ\AMQPDispatchers;

use MonsterMQ\Exceptions\ConnectionException;
use MonsterMQ\Exceptions\ProtocolException;
use MonsterMQ\Exceptions\SessionException;
use MonsterMQ\Interfaces\AMQPDispatchers\ConnectionDispatcher as ConnectionDispatcherInterface;
use MonsterMQ\Interfaces\Core\AuthenticationStrategy as AuthenticationStrategyInterface;

/**
 * Class ConnectionDispatcher responsible for authentication, setting session
 * properties, managing connection on application layer and so forth.
 * It uses prefixes of methods such as "send" and "receive" (to indicate
 * whether it receives or sends AMQP method) followed by AMQP method names.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class ConnectionDispatcher extends BaseDispatcher implements ConnectionDispatcherInterface
{
    /**
     * Authentication strategy. PLAIN and AMQPLAIN supported.
     *
     * @var AuthenticationStrategyInterface
     */
    public $authStrategy;

    /**
     * Receives Start AMQP method along with its arguments from server. This
     * arguments propose authentication method, locale and also server peer
     * properties.
     *
     * @throws ProtocolException
     * @throws SessionException
     */
    public function receiveStart(): array
    {
        $this->transmitter->sendRaw("AMQP\x0\x0\x9\x1");

        [$classId, $methodId] = $this->receiveClassAndMethod();

        if ($classId != static::CONNECTION_CLASS_ID && $methodId != static::CONNECTION_START) {
            throw new ProtocolException(
                "Unexpected method frame. Expecting class id '10' and method 
                id '10'. '{$classId}' and '{$methodId}' given."
            );

        }

        $versionMajor = $this->transmitter->receiveOctet();
        $versionMinor = $this->transmitter->receiveOctet();

        if ($versionMajor != static::SUPPORTED_MAJOR_VERSION
            && $versionMinor != static::SUPPORTED_MINOR_VERSION
        ) {
            throw new ProtocolException(
                "Unsupported server AMQP version - {$versionMajor}.{$versionMinor} , 
                while MonsterMQ supports only 0.9.1"
            );
        }

        $peerProperties = $this->transmitter->receiveFieldTable();
        $mechanisms = $this->transmitter->receiveLongStr();
        $locales = $this->transmitter->receiveLongStr();

        $this->validateFrameDelimiter();

        return [
            'peerProperties' => $peerProperties,
            'mechanisms' => $mechanisms,
            'locales' => $locales
        ];
    }

    /**
     * Selects security mechanism and locale.
     *
     * @param string $username          Account name.
     * @param string $password          Password for given account name.
     * @param string $locale            Locale which will be used during session.
     */
    public function sendStartOk(string $username, string $password, string $locale = 'en_US')
    {
        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort(static::SYSTEM_CHANNEL);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CONNECTION_CLASS_ID);
        $this->transmitter->sendShort(static::CONNECTION_START_OK);
        $this->transmitter->sendFieldTable(static::PEER_PROPERTIES);

        $this->transmitter->sendShortStr($this->authStrategy::AUTH_TYPE_NAME);

        $this->transmitter->sendLongStr(
            $this->authStrategy->getClientResponse($this->transmitter, $username, $password)
        );

        $this->transmitter->sendShortStr($locale);

        $this->transmitter->disableBuffering();
        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();
        $this->sendFrameDelimiter();
    }

    /**
     * Receive Tune AMQP method along with its arguments. This arguments
     * propose such session parameters as maximum channels number, maximum
     * frame size, and heartbeat timeout.
     *
     * @throws ProtocolException
     * @throws SessionException
     */
    public function receiveTune(): array
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if($classId !== static::CONNECTION_CLASS_ID || $methodId !== static::CONNECTION_TUNE) {
            throw new ProtocolException(
                "Unexpected method frame. Expecting class id '10' and method 
                id '30'. '{$classId}' and '{$methodId}' given.");
        }

        $channelMax = $this->transmitter->receiveShort();
        $frameMax = $this->transmitter->receiveLong();
        $heartbeat = $this->transmitter->receiveShort();

        $this->validateFrameDelimiter();

        return [
            "channelMaxNumber" => $channelMax,
            "frameMaxSize" => $frameMax,
            "heartbeat" => $heartbeat
        ];
    }

    /**
     * Negotiate connection tuning parameters.This method sends the client's
     * connection tuning parameters to the server. Certain fields are
     * negotiated, others provide capability information.
     *
     * @param int $channelMaxNumber Maximum number of channels to negotiate.
     * @param int $frameMaxSize   Maximum size of frame to negotiate.
     * @param int $heartbeat  This argument represents time within each
     *                        heartbeat frame must be sent in oder to keep
     *                        connection with server alive, if there was no
     *                        other sendings to the server. If there was no
     *                        sendings to or from server peer should close the
     *                        connection.
     */
    public function sendTuneOk(int $channelMaxNumber, int $frameMaxSize, int $heartbeat)
    {
        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort(static::SYSTEM_CHANNEL);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CONNECTION_CLASS_ID);
        $this->transmitter->sendShort(static::CONNECTION_TUNE_OK);
        $this->transmitter->sendShort($channelMaxNumber);
        $this->transmitter->sendLong($frameMaxSize);
        $this->transmitter->sendShort($heartbeat);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }

    /**
     * This method opens a connection to a virtual host, which is a collection
     * of resources, and acts to separate multiple application domains within
     * a server. The server may apply arbitrary limits per virtual host, such
     * as the number of each type of entity that may be used, per connection
     * and/or in total.
     *
     * @param string $path Virtual host to choose.
     */
    public function sendOpen(string $path = '/')
    {
        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort(static::SYSTEM_CHANNEL);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CONNECTION_CLASS_ID);
        $this->transmitter->sendShort(static::CONNECTION_OPEN);
        $this->transmitter->sendShortStr($path);
        //following short string reserved by AMQP, now its value makes no sense
        $this->transmitter->sendShortStr('');
        //following octet reserved by AMQP, now its value makes no sense
        $this->transmitter->sendOctet(0);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }

    /**
     * This method signals to the client that the connection is ready for use.
     *
     * @throws ProtocolException
     * @throws SessionException
     */
    public function receiveOpenOk()
    {
        [$classId, $methodId] = $this->receiveClassAndMethod();

        if($classId !== static::CONNECTION_CLASS_ID || $methodId !== static::CONNECTION_OPEN_OK) {
            throw new ProtocolException(
                "Unexpected method frame. Expecting class id '10' and method 
                id '41'. '{$classId}' and '{$methodId}' given.");
        }

        //Following short string argument reserved by AMQP
        $this->transmitter->receiveShortStr();

        $this->validateFrameDelimiter();
    }

    /**
     * Initiates connection closure. This may be due to internal conditions
     * (e.g. a forced shut-down) or due to an error handling a specific method,
     * i.e. an exception. When a close is due to an exception, the sender
     * provides the class and method id of the method which caused the
     * exception.
     *
     * @param int $replyCode    Reply code.
     * @param string $replyText Reply text.
     * @param int $classId      When the close is provoked by a method
     *                          exception, this is the class of the method.
     * @param int $methodId     When the close is provoked by a method
     *                          exception, this is the ID of the method.
     */
    public function sendClose(int $replyCode, string $replyText, int $classId = null, int $methodId = null)
    {
        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort(static::SYSTEM_CHANNEL);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CONNECTION_CLASS_ID);
        $this->transmitter->sendShort(static::CONNECTION_CLOSE);
        $this->transmitter->sendShort($replyCode);
        $this->transmitter->sendShortStr($replyText);
        $this->transmitter->sendShort($classId);
        $this->transmitter->sendShort($methodId);
        $this->transmitter->disableBuffering();

        $this->transmitter->sendLong($this->transmitter->bufferLength());
        $this->transmitter->sendBuffer();

        $this->sendFrameDelimiter();
    }

    /**
     * Waits for incoming close-ok AMQP method in oder to close the connection.
     * Client may close the connection if there was no incoming close-ok method
     * during the close-ok timout, which may be specified by setCloseOkTimout.
     *
     * @throws ConnectionException
     * @throws SessionException
     * @throws ProtocolException
     */
    public function receiveCloseOK()
    {
        $start = time();
        do {
            if (time() >= $start + $this->closeOkTimeout) {
                throw new ConnectionException(
                    "close-ok method expectation have been timed out. Connection has been closed."
                );
            }

            [$classId, $methodId] = $this->receiveClassAndMethod();

            if ($classId == static::CONNECTION_CLASS_ID && $methodId == static::CONNECTION_CLOSE_OK) {
                $this->socket->close();
            }
        } while($classId != static::CONNECTION_CLASS_ID && $methodId != static::CONNECTION_CLOSE_OK);
    }
}