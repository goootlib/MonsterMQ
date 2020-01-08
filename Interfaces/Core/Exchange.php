<?php


namespace MonsterMQ\Interfaces\Core;


interface Exchange
{
    /**
     * Sets current exchange for further operations.
     *
     * @param string $exchange Current exchange name.
     */
    public function setCurrentExchangeName(string $exchange);

    /**
     * Sets exchange type going to be declared.
     *
     * @param string $type Exchange type going to be declared.
     */
    public function setExchangeType(string $type);

    /**
     * Declares exchanges with currently set arguments.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function declare();

    /**
     * Deletes current exchange.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function delete();

    /**
     * Binds current exchange to another exchange with routing key.
     *
     * @param string $source      Exchange name to bind.
     * @param string $routingKey  Routing key of binding.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function bind(string $source, string $routingKey);

    /**
     * Unbinds current exchange from bound exchange. Routing key also must be
     * specified.
     *
     * @param string $source      Source exchange that going to be unbound.
     * @param string $routingKey  Routing key for unbinding.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function unbind(string $source, string $routingKey);

    /**
     * Sets exchange durable. Durable exchanges remains after server restart.
     *
     * @return \MonsterMQ\Core\Exchange
     */
    public function setDurable();

    /**
     * Sets exchange autodelete. Autodelete exchanges delete if no consumers
     * left.
     *
     * @return \MonsterMQ\Core\Exchange
     */
    public function setAutodelete();
}