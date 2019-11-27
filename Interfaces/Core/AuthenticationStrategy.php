<?php


namespace MonsterMQ\Interfaces\Core;


use MonsterMQ\Interfaces\Connections\BinaryTransmitter as BinaryTransmitterInerface;

interface AuthenticationStrategy
{
    public function getClientResponse(BinaryTransmitterInerface $transmitter, string $username, string $password): string;
}