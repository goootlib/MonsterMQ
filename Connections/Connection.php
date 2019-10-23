<?php


namespace MonsterMQ\Connections;

use MonsterMQ\Interfaces\IO\IO as IOInterface;
use MonsterMQ\Transmitter\SocketIO;

class Connection
{
    protected $io;

    public function __construct (IOInterface $io = null)
    {
        if(!isset($io)){
            $this->io = new SocketIO();
        }
        $this->io = $io;
    }

}