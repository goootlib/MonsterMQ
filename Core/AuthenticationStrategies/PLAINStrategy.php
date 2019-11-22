<?php


namespace MonsterMQ\Core\AuthenticationStrategies;


use MonsterMQ\Interfaces\BinaryTransmitter as BinaryTransmitterInterface;

class PLAINStrategy
{
    public function execute(BinaryTransmitterInterface $transmitter,string $username, string $password)
    {

    }
}