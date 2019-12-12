<?php


namespace MonsterMQ\Core;


use MonsterMQ\Client\BaseClient;
use MonsterMQ\Interfaces\AMQPDispatchers\ExchangeDispatcher as ExchangeDispatcherInterface;

/**
 * Following class provides API for exchange declaration.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class ExchangeDeclarator
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
     * Name of the currently declaring exchange.
     *
     * @var string
     */
    protected $name;

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
     * ExchangeDeclarator constructor.
     *
     * @param ExchangeDispatcherInterface $dispatcher
     * @param BaseClient                  $client
     */
    public function __construct(ExchangeDispatcherInterface $dispatcher, BaseClient $client)
    {
        $this->exchangeDispatcher = $dispatcher;
        $this->client = $client;
    }

    /**
     * Declares exchanges with currently set arguments.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function declare()
    {
        $this->exchangeDispatcher->sendDeclare(
            $this->client->currentChannel(),
            $this->name,
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
        $this->name = null;
        $this->type = 'direct';
        $this->durable = false;
        $this->autodelete = false;
    }

    /**
     * Sets exchange name for current declaration.
     *
     * @param string $name Exchange name to be declared.
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * Sets exchange type for current declaration. Supported types are
     * "direct, fanout, topic".
     *
     * @param string $type Exchange type to be declared.
     */
    public function setType(string $type = 'direct')
    {
        $this->type = $type;
    }

    /**
     * Sets exchange durable. Durable exchanges remains after server restart.
     */
    public function setDurable()
    {
        $this->durable = true;
    }

    /**
     * Sets exchange autodelete. Autodelete exchanges delete if no consumers
     * left.
     */
    public function setAutodelete()
    {
        $this->autodelete = true;
    }
}