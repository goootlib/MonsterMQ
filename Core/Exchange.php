<?php


namespace MonsterMQ\Core;


use MonsterMQ\Client\BaseClient;
use MonsterMQ\Interfaces\AMQPDispatchers\ExchangeDispatcher as ExchangeDispatcherInterface;
use MonsterMQ\Interfaces\Core\Exchange as ExchangeInterface;
use MonsterMQ\Interfaces\Support\Logger as LoggerInterface;

/**
 * This class provides API for managing exchanges.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
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
     * Logger instance.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Source exchange for deleting, binding and unbinding.
     *
     * @var string
     */
    protected $currentExchangeName;

    /**
     * Type of the currently declaring exchange.
     *
     * @var string
     */
    protected $type = 'direct';

    /**
     * Whether currently declared exchange is durable
     *
     * @var bool
     */
    protected $durable = false;

    /**
     * Whether currently declaring exchange will be deleted if no consumers
     * left.
     *
     * @var bool
     */
    protected $autodelete = false;


    /**
     * Exchange constructor.
     *
     * @param ExchangeDispatcherInterface $dispatcher
     */
    public function __construct(ExchangeDispatcherInterface $dispatcher, BaseClient $client, LoggerInterface $logger)
    {
        $this->exchangeDispatcher = $dispatcher;
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * Declares exchanges with currently set arguments.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function declare()
    {
        $channel = $this->client->currentChannel();
        $durable = $this->durable ? 'durable' : "";
        $autodelete = $this->autodelete ? "autodelete" : "";
        $this->logger->write(
            "Declaring {$durable} {$autodelete} exchange on channel {$channel} with name '{$this->currentExchangeName}'
            and type '{$this->type}'"
        );

        $this->exchangeDispatcher->sendDeclare(
            $this->client->currentChannel(),
            $this->currentExchangeName,
            $this->type,
            false,
            $this->durable,
            $this->autodelete
        );
        $this->exchangeDispatcher->receiveDeclareOk();

        $this->flushArguments();
    }

    /**
     * Flushes arguments for declaration.
     */
    protected function flushArguments()
    {
        $this->currentExchangeName = null;
        $this->type = 'direct';
        $this->durable = false;
        $this->autodelete = false;
    }

    /**
     * Deletes current exchange.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function delete()
    {
        $channel = $this->client->currentChannel();
        $this->logger->write("Deleting exchange with name {$this->currentExchangeName} on channel {$channel}");

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
    public function bind(string $to, string $routingKey)
    {
        $channel = $this->client->currentChannel();
        $this->logger->write(
            "Binding exchange '{$this->currentExchangeName}' to exchange '{$to}'
             with routing key '{$routingKey}' on channel {$channel}"
        );

        $this->exchangeDispatcher->sendBind(
            $this->client->currentChannel(),
            $this->currentExchangeName,
            $to,
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
    public function unbind(string $from, string $routingKey)
    {
        $channel = $this->client->currentChannel();
        $this->logger->write(
            "Unbinding exchange {$this->currentExchangeName} from exchange {$from} 
            with routing key {$routingKey} on channel {$channel}"
        );

        $this->exchangeDispatcher->sendUnbind(
            $this->client->currentChannel(),
            $this->currentExchangeName,
            $from,
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

    /**
     * Sets exchange type going to be declared.
     *
     * @param string $type Exchange type going to be declared.
     */
    public function setExchangeType(string $type)
    {
        $this->type = $type;
    }

    /**
     * Sets exchange durable. Durable exchanges remains after server restart.
     *
     * @return $this
     */
    public function setDurable()
    {
        $this->durable = true;

        return $this;
    }

    /**
     * Sets exchange autodelete. Autodelete exchanges delete if no consumers
     * left.
     *
     * @return $this
     */
    public function setAutodelete()
    {
        $this->autodelete = true;

        return $this;
    }
}