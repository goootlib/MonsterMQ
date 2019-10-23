<?php


namespace MonsterMQ\Client;

use MonsterMQ\Interfaces\IO\IO as IOInterface;

/**
 * This class provides API for publishing messages
 * and other interacting with AMQP server.
 */
class Producer
{
    public $io;

    /**
     * Transmitter instance.
     * @see Transmitters folder
     * @var MonsterMQ\Interfaces\Transmitter
     */
    protected $transmitter;

    public function __construct(IOInterface $transmitter)
    {
        $this->io = $transmitter;
    }

    /**
     * Returns transmitter instance.
     * @return MonsterMQ\Interfaces\Transmitter|TransmitterInterface
     */
    public function connection()
    {
        return $this->io;
    }

}