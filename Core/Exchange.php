<?php


namespace MonsterMQ\Core;


use MonsterMQ\Client\BaseClient;
use MonsterMQ\Interfaces\AMQPDispatchers\ExchangeDispatcher as ExchangeDispatcherInterface;
use MonsterMQ\Interfaces\Core\Exchange as ExchangeInterface;

class Exchange implements ExchangeInterface
{
    /**
     * Client instance.
     *
     * @var BaseClient
     */
    protected $client;

    /**
     * Exchange dispatcher instance.
     *
     * @var ExchangeDispatcherInterface
     */
    protected $exchangeDispatcher;

    /**
     * Source exchange for deleting, binding and unbinding.
     *
     * @var string
     */
    protected $currentExchangeName;

    /**
     * Exchange constructor.
     *
     * @param ExchangeDispatcherInterface $dispatcher
     */
    public function __construct(ExchangeDispatcherInterface $dispatcher, BaseClient $client)
    {
        $this->exchangeDispatcher = $dispatcher;
        $this->client = $client;
    }

    /**
     * Deletes current exchange.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function delete()
    {
        $this->exchangeDispatcher->sendDelete(
            $this->client->currentChannel(),
            $this->currentExchangeName
        );
        $this->exchangeDispatcher->receiveDeleteOk();
    }

    /**
     * Binds current exchange to another exchange with routing key.
     *
     * @param string $source      Exchange name to bind.
     * @param string $routingKey  Routing key of binding.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function bind(string $source, string $routingKey)
    {
        $this->exchangeDispatcher->sendBind(
            $this->client->currentChannel(),
            $this->currentExchangeName,
            $source,
            $routingKey
        );
        $this->exchangeDispatcher->receiveBindOk();
    }

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
    public function unbind(string $source, string $routingKey)
    {
        $this->exchangeDispatcher->sendUnbind(
            $this->client->currentChannel(),
            $this->currentExchangeName,
            $source,
            $routingKey
        );
        $this->exchangeDispatcher->receiveUnbindOk();
    }

    /**
     * Sets current exchange for further operations.
     *
     * @param string $exchange Current exchange name.
     */
    public function setCurrentExchangeName(string $exchange)
    {
        $this->currentExchangeName = $exchange;
    }
}