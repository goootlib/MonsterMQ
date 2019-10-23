<?php


namespace MonsterMQ\Classes;

use MonsterMQ\Interfaces\BinaryTransmitter\BinaryTransmitter;
use MonsterMQ\Interfaces\Classes\Connection as ConnectionClassInterface;

class Connection implements ConnectionClassInterface
{
    protected $binaryTransmitter;

    public function __construct(BinaryTransmitter $transmitter)
    {
        $this->binaryTransmitter = $transmitter;
    }

    /**
     * Select security mechanism and locale.
     * This method selects a SASL security mechanism.
     */
    public function start_ok()
    {
        $this->binaryTransmitter->sendOctet(1);
        $this->binaryTransmitter->sendShort(0);

        $this->binaryTransmitter->sendLong();
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