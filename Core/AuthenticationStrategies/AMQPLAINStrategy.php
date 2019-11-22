<?php


namespace MonsterMQ\Core\AuthenticationStrategies;


use MonsterMQ\Connections\TableValuePacker;
use MonsterMQ\Interfaces\BinaryTransmitter as BinaryTransmitterInterface;

class AMQPLAINStrategy
{
    public const AUTH_TYPE_NAME = 'AMQPLAIN';

    /**
     * Executes AMQPLAIN authentication algorithm.
     *
     * @param BinaryTransmitterInterface $transmitter Transmitter instance.
     * @param string                     $username    Given username.
     * @param string                     $password    Given password.
     *
     * @throws \MonsterMQ\Exceptions\PackerException
     */
    public function execute(
        BinaryTransmitterInterface $transmitter,
        string $username,
        string $password
    ){
        $packer = new TableValuePacker($transmitter);
        $usernameKey = $packer->packFieldTableValue('s','LOGIN');
        $username = $packer->packFieldTableValue('S',$username);
        $username = $usernameKey.'S'.$username;

        $passwordKey =  $packer->packFieldTableValue('s',"PASSWORD");
        $password = $packer->packFieldTableValue('S',$password);
        $password = $passwordKey.'S'.$password;

        $transmitter->sendLongStr($username.$password);
    }
}