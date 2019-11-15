<?php


namespace MonsterMQ\AMQPDispatchers;

use MonsterMQ\Connections\TableValuePacker;
use MonsterMQ\Exceptions\ConnectionDispatcherException;
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

    public function receive_start()
    {
        $this->transmitter->sendRaw("AMQP\x0\x0\x9\x1");
        //Receive frame header
        $frameType = $this->transmitter->receiveOctet();
        $chanel = $this->transmitter->receiveShort();
        $size = $this->transmitter->receiveLong();

        if($frameType == static::METHOD_FRAME_TYPE && $chanel == 0) {
            $classId = $this->transmitter->receiveShort();
            $methodId = $this->transmitter->receiveShort();
        }else{
            return;
        }

        if($classId == static::CLASS_ID && $methodId == static::START_METHOD_ID) {
            $versionMajor = $this->transmitter->receiveOctet();
            $versionMinor = $this->transmitter->receiveOctet();
        }else{
            return;
        }

        if($versionMajor == static::SUPPORTED_MAJOR_VERSION && $versionMinor == static::SUPPORTED_MINOR_VERSION) {
            $peerProperties = $this->transmitter->receiveFieldTable();
            $mechanisms = $this->transmitter->receiveLongStr();
            $locales = $this->transmitter->receiveLongStr();
        }else{
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
     */
    public function send_start_ok()
    {
        $this->transmitter->sendOctet(1);
        $this->transmitter->sendShort(0);

        $this->transmitter->enableBuffering();
        $this->transmitter->sendShort(static::CLASS_ID);
        $this->transmitter->sendShort(static::START_OK_METHOD_ID);
        $this->transmitter->sendFieldTable([
            'product' => array('S', 'MonsterMQ'),
            'platform' => array('S', 'PHP'),
            'version' => array('S', '0.1.0'),
            'copyright' => array('S', '')

        ]);

        $this->transmitter->sendShortStr('AMQPLAIN');

        $packer = new TableValuePacker($this->transmitter);
        $loginKey = $packer->packFieldTableValue('s','LOGIN');
        $username = $packer->packFieldTableValue('S','guest');
        $username = $loginKey.'S'.$username;

        $passwordKey =  $packer->packFieldTableValue('s',"PASSWORD");
        $password = $packer->packFieldTableValue('S','guest');
        $password = $passwordKey.'S'.$password;

        var_dump($username.$password);
        $this->transmitter->sendLongStr($username.$password);

        $this->transmitter->sendShortStr('en_US');

        $this->transmitter->disableBuffering();
        $this->transmitter->sendBufferLength();
        var_dump($this->transmitter->getBufferContent());
        $this->transmitter->sendBuffer();

        $this->transmitter->sendOctet(0xCE);
    }

    public function receive_secure()
    {
        //var_dump($this->transmitter->receiveRaw(1));

        $frameType = $this->transmitter->receiveOctet();
        $channel = $this->transmitter->receiveShort();
        $size = $this->transmitter->receiveLong();


        //$response = $this->transmitter->receiveLongStr();
        var_dump($frameType, $channel, $size);

    }

    public function secure_ok()
    {
        // TODO: Implement secure_ok() method.
    }

    public function tune_ok()
    {
        // TODO: Implement tune_ok() method.
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