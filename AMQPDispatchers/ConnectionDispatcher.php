<?php


namespace MonsterMQ\AMQPDispatchers;

use MonsterMQ\Connections\TableValuePacker;
use MonsterMQ\Exceptions\ConnectionDispatcherException;
use MonsterMQ\Exceptions\PackerException;
use MonsterMQ\Exceptions\ProtocolException;
use MonsterMQ\Interfaces\AMQPDispatchers\ConnectionDispatcher as ConnectionDispatcherInterface;
use MonsterMQ\Interfaces\BinaryTransmitter;
use MonsterMQ\Support\FrameDelimiting;

/**
 * Class ConnectionDispatcher responsible for authentication, setting session
 * properties, managing connection on application layer and so forth. It uses
 * it's own naming convention to comply existing AMQP method naming convention.
 * It uses prefixes like "send" and "receive" (to indicate whether it receives
 * or sends AMQP method) followed by names of AMQP methods.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class ConnectionDispatcher implements ConnectionDispatcherInterface
{
    use FrameDelimiting;

    /**
     * Binary transmitter instance.
     *
     * @var BinaryTransmitter
     */
    protected $transmitter;

    /**
     * Authentication strategy. PLAIN and AMQPLAIN supported.
     *
     * @var
     */
    public $authStrategy;

    /**
     * ConnectionDispatcher constructor.
     *
     * @param BinaryTransmitter $transmitter
     */
    public function __construct(BinaryTransmitter $transmitter)
    {
        $this->transmitter = $transmitter;
    }

    /**
     * Receives Start AMQP method along with its arguments from server. This
     * arguments propose authentication method, locale and also server peer
     * properties.
     */
    public function receive_start(): array
    {
        $this->transmitter->sendRaw("AMQP\x0\x0\x9\x1");
        //Receive frame header
        $frameType = $this->transmitter->receiveOctet();
        $channel = $this->transmitter->receiveShort();
        $size = $this->transmitter->receiveLong();

        if ($frameType == static::METHOD_FRAME_TYPE && $channel == 0) {
            $classId = $this->transmitter->receiveShort();
            $methodId = $this->transmitter->receiveShort();
        } else {
            throw new ProtocolException(
                'Unexpected frame type or chanell number on receiving 
                Connection.Start method.'
            );
        }

        if ($classId == static::CLASS_ID
            && $methodId == static::START_METHOD_ID
        ) {
            $versionMajor = $this->transmitter->receiveOctet();
            $versionMinor = $this->transmitter->receiveOctet();
        } else {
            throw new ProtocolException(
                "Unexpected method frame. Expecting class id '10' and method 
                id '10'. '{$classId}' and '{$methodId}' given.");
        }

        if ($versionMajor == static::SUPPORTED_MAJOR_VERSION
            && $versionMinor == static::SUPPORTED_MINOR_VERSION
        ) {
            $peerProperties = $this->transmitter->receiveFieldTable();
            $mechanisms = $this->transmitter->receiveLongStr();
            $locales = $this->transmitter->receiveLongStr();
        } else {
            throw new ConnectionException(
                "Unsupported server AMQP version - {$versionMajor}.{$versionMinor} , 
                while MonsterMQ supports 0.9.1"
            );
        }

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
     *
     * @throws PackerException In case of unsupported field table type encounter.
     */
    public function send_start_ok(
        string $username,
        string $password,
        string $locale = 'en_US'
    ){
        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort(0);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CLASS_ID);
        $this->transmitter->sendShort(static::START_OK_METHOD_ID);
        $this->transmitter->sendFieldTable(static::PEER_PROPERTIES);

        $this->transmitter->sendShortStr($this->authStrategy::AUTH_TYPE_NAME);

        $this->authStrategy->execute($this, $username, $password);

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
     */
    public function receive_tune(): array
    {
        $frameType = $this->transmitter->receiveOctet();
        $channel = $this->transmitter->receiveShort();
        if ($frameType !== static::METHOD_FRAME_TYPE || $channel !== 0) {
            throw new ProtocolException(
                "Unexpected frame type or channel number on receiving 
                Connection.tune method."
            );
        }
        $size = $this->transmitter->receiveLong();
        $classId = $this->transmitter->receiveShort();
        $methodId = $this->transmitter->receiveShort();
        if($classId !== static::CLASS_ID || $methodId !== static::TUNE_METHOD_ID) {
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
     * @param int $channelMax Maximum number of channels to negotiate.
     * @param int $frameMax   Maximum size of frame to negotiate.
     * @param int $heartbeat  This argument represents time within each
     *                        heartbeat frame must be sent in oder to keep
     *                        connection with server alive, if there was no
     *                        other sendings to the server. If there was no
     *                        sendings to or from server peer should close the
     *                        connection.
     */
    public function send_tune_ok(int $channelMaxNumber, int $frameMaxSize, int $heartbeat)
    {
        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort(0);
        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CLASS_ID);
        $this->transmitter->sendShort(static::TUNE_OK_METHOD_ID);
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
    public function send_open(string $path = '/')
    {
        $this->transmitter->sendOctet(static::METHOD_FRAME_TYPE);
        $this->transmitter->sendShort(0);
        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CLASS_ID);
        $this->transmitter->sendShort(static::OPEN_METHOD_ID);
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
     */
    public function receive_open_ok()
    {
        $frameType = $this->transmitter->receiveOctet();
        $channel = $this->transmitter->receiveShort();
        if ($frameType !== static::METHOD_FRAME_TYPE || $channel !== 0) {
            throw new ProtocolException(
                "Unexpected frame type or channel number on receiving 
                Connection.open_ok method."
            );
        }
        $size = $this->transmitter->receiveLong();
        $classId = $this->transmitter->receiveShort();
        $methodId = $this->transmitter->receiveShort();
        if($classId !== static::CLASS_ID || $methodId !== static::OPEN_OK_METHOD_ID) {
            throw new ProtocolException(
                "Unexpected method frame. Expecting class id '10' and method 
                id '41'. '{$classId}' and '{$methodId}' given.");
        }
        $this->transmitter->receiveShortStr();
        $this->validateFrameDelimiter();
    }

    public function close()
    {
        // TODO: Implement close() method.
    }

    public function close_ok()
    {
        // TODO: Implement close_ok() method.
    }
}