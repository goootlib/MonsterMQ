<?php


namespace MonsterMQ\Core;

use MonsterMQ\Core\AuthenticationStrategies\AMQPLAINStrategy;
use MonsterMQ\Core\AuthenticationStrategies\PLAINStrategy;
use MonsterMQ\Exceptions\ConnectionException;
use MonsterMQ\Interfaces\AMQPDispatchers\ConnectionDispatcher as ConnectionDispatcherInterface;

class Connection
{
    /**
     * Connection dispatcher instance.
     *
     * @var ConnectionDispatcherInterface
     */
    protected $connectionDispatcher;

    /**
     * Virtual host to be opened.
     *
     * @var string
     */
    protected $virtualHost = "/";

    /**
     * Connection constructor.
     *
     * @param ConnectionDispatcherInterface $dispatcher
     */
    public function __construct(ConnectionDispatcherInterface $dispatcher)
    {
        $this->connectionDispatcher = $dispatcher;
    }

    public function logIn(string $username = 'guest', string $password = 'guest')
    {
        $this->startSession($username, $password);

        $this->tuneSession();

        $this->openVirtualHost();
    }

    protected function selectAuthStrategy(string $securityMechanisms)
    {
        $securityMechanisms = explode(' ', $securityMechanisms);

        if (in_array('AMQPLAIN',$securityMechanisms)) {
            $this->connectionDispatcher->authStrategy = new AMQPLAINStrategy();
        } elseif (in_array('PLAIN', $securityMechanisms)) {
            $this->connectionDispatcher->authStrategy = new PLAINStrategy();
        } else {
            throw new ConnectionException(
                'No supported authentication method. MonsterMQ supports only
                PLAIN and AMQPLAIN.'
            );
        }
    }

    protected function startSession(string $username, string $password)
    {
        $properties = $this->connectionDispatcher->receive_start();

        $this->selectAuthStrategy($properties['mechanisms']);
        $this->connectionDispatcher->send_start_ok($username, $password, 'en_US');
    }

    protected function tuneSession()
    {
        $properties = $this->connectionDispatcher->receive_tune();
        $this->connectionDispatcher->send_tune_ok(
            $properties['channelMaxNumber'],
            $properties['frameMaxSize'],
            $properties['heartbeat']
        );
    }

    protected function openVirtualHost()
    {
        $this->connectionDispatcher->send_open($this->virtualHost);
        $this->connectionDispatcher->receive_open_ok();
    }

    public function virtualHost(string $vhost)
    {
        $this->virtualHost = $vhost;
    }
}