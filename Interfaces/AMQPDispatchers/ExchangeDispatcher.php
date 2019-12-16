<?php


namespace MonsterMQ\Interfaces\AMQPDispatchers;


use MonsterMQ\Exceptions\ProtocolException;
use MonsterMQ\Exceptions\SessionException;

interface ExchangeDispatcher extends AMQP
{
    /**
     * This method creates an exchange if it does not already exist, and if the
     * exchange exists, verifies that it is of the correct and expected class.
     *
     * @param int    $channel    Channel that going to be used.
     * @param string $name       Exchange name to be declared.The exchange name
     *                           consists of a non-empty sequence of these
     *                           characters: letters, digits, hyphen, underscore,
     *                           period, or colon.
     * @param string $type       Exchange type to be declared. Supported "direct,
     *                           fanout, topic".
     * @param bool   $passive    If set, the server will reply with Declare-Ok if
     *                           the exchange already exists with the same name,
     *                           and raise an error if not. A declare with both
     *                           passive and no-wait has no effect.
     * @param bool   $durable    Durable exchanges remain active when a server restarts.
     * @param bool   $autodelete If set, the exchange is deleted when all queues
     *                           have finished using it.
     * @param bool   $internal   If set, the exchange may not be used directly
     *                           by publishers, but only when bound to other
     *                           exchanges.
     * @param bool   $noWait     Whether server will respond with Declare-Ok.
     * @param array  $arguments  A set of arguments for the declaration. The
     *                           syntax and semantics of these arguments depends
     *                           on the server implementation.
     */
    public function sendDeclare(
        int $channek,
        string $name,
        string $type = 'direct',
        bool $passive = false,
        bool $durable = true,
        bool $autodelete = false,
        bool $internal = false,
        bool $noWait = false,
        array $arguments = []
    );

    /**
     * Confirms exchange declaration.
     *
     * @throws ProtocolException
     * @throws SessionException
     */
    public function receiveDeclareOk();

    /**
     * This method deletes an exchange. When an exchange is deleted all queue
     * bindings on the exchange are cancelled.
     *
     * @param int    $channel Channel that going to be used.
     * @param string $name    Exchange name that going to be deleted.
     * @param bool   $unused  If set, the server will only delete the exchange
     *                        if it has no queue bindings. If the exchange has
     *                        queue bindings the server does not delete it but
     *                        raises a channel exception instead.
     * @param bool   $noWait  Whether the server will respond with Declare-Ok.
     */
    public function sendDelete(int $channel,string $name, bool $unused = false, bool $noWait = false);

    /**
     * This method confirms the deletion of an exchange.
     *
     * @throws ProtocolException
     * @throws SessionException
     */
    public function receiveDeleteOk();

    /**
     * Binds exchange to another exchange.
     *
     * @param int    $channel     Channel number that going to be used.
     * @param string $destination The name of the destination exchange to bind.
     * @param string $source      The name of the source exchange to bind.
     * @param string $routingKey  The routing key for the binding.
     * @param bool   $noWait      Whether the server will respond with bind-Ok.
     * @param array  $arguments   A set of arguments for the binding. The syntax
     *                            and semantics of these arguments depends on
     *                            the exchange class.
     */
    public function sendBind(
        int $channel,
        string $destination,
        string $source,
        string $routingKey,
        bool $noWait = false,
        array $arguments = []
    );

    /**
     * This method confirms that the binding was successful.
     *
     * @throws ProtocolException
     * @throws SessionException
     */
    public function receiveBindOk();

    /**
     * Unbind an exchange from an exchange.
     *
     * @param int    $channel     Channel number that going to be used.
     * @param string $destination The name of the destination exchange to unbind.
     * @param string $source      The name of the source exchange to unbind.
     * @param string $routingKey  The routing key of the binding to unbind.
     * @param bool   $noWait      Whether the server will respond with Unbind-Ok.
     * @param array  $arguments   Specifies the arguments of the binding to unbind.
     */
    public function sendUnbind(
        int $channel,
        string $destination,
        string $source,
        string $routingKey,
        bool $noWait = false,
        array $argument = []
    );

    /**
     * This method confirms that the unbinding was successful.
     *
     * @throws ProtocolException
     * @throws SessionException
     */
    public function receiveUnbindOk();
}