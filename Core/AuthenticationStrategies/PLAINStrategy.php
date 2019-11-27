<?php


namespace MonsterMQ\Core\AuthenticationStrategies;


use MonsterMQ\Interfaces\Connections\BinaryTransmitter as BinaryTransmitterInterface;
use MonsterMQ\Interfaces\Core\AuthenticationStrategy as AuthenticationStrategyInterface;

/**
 * This class responsible for authentication within AMQP connection
 * establishment.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class PLAINStrategy implements AuthenticationStrategyInterface
{
    public const AUTH_TYPE_NAME = "PLAIN";

    /**
     * Returns SASL client response.
     *
     * @param BinaryTransmitterInterface $transmitter Transmitter instance.
     * @param string                     $username    Given username.
     * @param string                     $password    Given password.
     *
     * @return string SASL client response
     */
    public function getClientResponse(
        BinaryTransmitterInterface $transmitter,
        string $username,
        string $password
    ): string {
        $saslPayload = "\x00{$username}\x00{$password}";
        return $saslPayload;
    }
}