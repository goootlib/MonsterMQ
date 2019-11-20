<?php


namespace MonsterMQ\AMQPDispatchers;

use MonsterMQ\Connections\TableValuePacker;
use MonsterMQ\Exceptions\ConnectionDispatcherException;
use MonsterMQ\Exceptions\PackerException;
use MonsterMQ\Exceptions\ProtocolException;
use MonsterMQ\Interfaces\AMQPDispatchers\ConnectionDispatcher as ConnectionDispatcherInterface;
use MonsterMQ\Interfaces\BinaryTransmitter;
use MonsterMQ\Support\FrameDelimiting;


class ConnectionDispatcher implements ConnectionDispatcherInterface
{
    use FrameDelimiting;

    /**
     * Binary transmitter instance.
     *
     * @var BinaryTransmitter
     */
    protected $transmitter;

    public function __construct(BinaryTransmitter $transmitter)
    {
        $this->transmitter = $transmitter;
    }

    /**
     * Receives Start AMQP method along with its arguments from server.
     */
    public function receive_start()
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

        $mechanisms = explode(" ", $mechanisms);
        if(!in_array("PLAIN", $mechanisms) && !in_array("AMQPLAIN", $mechanisms)){
            throw new ConnectionDispatcherException('There are no supported security mechanisms.');
        }
        $this->validateFrameDelimiter();
    }

    /**
     * Select security mechanism and locale.
     *
     * @param string $securityMechanism MonsterMQ supports PLAIN and AMQPLAIN mechanisms.
     * @param string $username          Account name.
     * @param string $password          Password for given account name.
     * @param string $locale            Locale which will be used during session.
     *
     * @throws PackerException In case of unsupported field table type encounter.
     */
    public function send_start_ok(
        string $securityMechanism,
        string $username,
        string $password,
        string $locale
    ){
        $this->transmitter->sendOctet(1);
        $this->transmitter->sendShort(0);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CLASS_ID);
        $this->transmitter->sendShort(static::START_OK_METHOD_ID);
        $this->transmitter->sendFieldTable(static::PEER_PROPERTIES);

        $this->transmitter->sendShortStr($securityMechanism);

        $packer = new TableValuePacker($this->transmitter);
        $usernameKey = $packer->packFieldTableValue('s','LOGIN');
        $username = $packer->packFieldTableValue('S',$username);
        $username = $usernameKey.'S'.$username;

        $passwordKey =  $packer->packFieldTableValue('s',"PASSWORD");
        $password = $packer->packFieldTableValue('S',$password);
        $password = $passwordKey.'S'.$password;

        $this->transmitter->sendLongStr($username.$password);

        $this->transmitter->sendShortStr($locale);

        $this->transmitter->disableBuffering();
        $this->transmitter->sendBufferLength();
        $this->transmitter->sendBuffer();

        $this->transmitter->sendOctet(0xCE);
    }

    /**
     * Receive Tune AMQP method along with its arguments.
     */
    public function receive_tune()
    {
        $frameType = $this->transmitter->receiveOctet();
        $channel = $this->transmitter->receiveShort();
        if ($frameType !== static::METHOD_FRAME_TYPE || $channel !== 0) {
            throw new ProtocolException(
                'Unexpected frame type or chanell number on receiving 
                Connection.Tune method.'
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
            "channelMax" => $channelMax,
            "frameMax" => $frameMax,
            "heartbeat" => $heartbeat
        ];
    }

    /**
     * Negotiate connection tuning parameters.This method sends the client's
     * connection tuning parameters to the server. Certain fields are
     * negotiated, others provide capability information.
     */
    public function send_tune_ok(int $channelMax, int $frameMax, int $heartbeat)
    {
        $this->transmitter->sendOctet(1);
        $this->transmitter->sendShort(0);
        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort($channelMax);
        $this->transmitter->sendLong($frameMax);
        $this->transmitter->sendShort($heartbeat);
        $this->transmitter->disableBuffering();
        $this->transmitter->sendBufferLength();
        $this->transmitter->sendBuffer();
        $this->sendFrameDelimiter();
    }

    public function open()
    {
        // TODO: Implement open() method.
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