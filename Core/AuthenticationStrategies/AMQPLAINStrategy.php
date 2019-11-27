<?php


namespace MonsterMQ\Core\AuthenticationStrategies;


use MonsterMQ\Connections\TableValuePacker;
use MonsterMQ\Interfaces\Connections\BinaryTransmitter as BinaryTransmitterInterface;
use MonsterMQ\Interfaces\Core\AuthenticationStrategy as AuthenticationStrategyInterface;

/**
 * This class responsible for authentication within AMQP connection
 * establishment.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class AMQPLAINStrategy implements AuthenticationStrategyInterface
{
    public const AUTH_TYPE_NAME = 'AMQPLAIN';

    /**
     * Returns AMQPLAIN authentication client response.
     *
     * @param BinaryTransmitterInterface $transmitter Transmitter instance.
     * @param string                     $username    Given username.
     * @param string                     $password    Given password.
     *
     * @return string AMQPLAIN client response.
     *
     * @throws \MonsterMQ\Exceptions\PackerException
     */
    public function getClientResponse(
        BinaryTransmitterInterface $transmitter,
        string $username,
        string $password
    ): string {
        $packer = new TableValuePacker($transmitter);
        $usernameKey = $packer->packFieldTableValue('s','LOGIN');
        $username = $packer->packFieldTableValue('S',$username);
        $username = $usernameKey.'S'.$username;

        $passwordKey =  $packer->packFieldTableValue('s',"PASSWORD");
        $password = $packer->packFieldTableValue('S',$password);
        $password = $passwordKey.'S'.$password;

        return $username.$password;
    }
}