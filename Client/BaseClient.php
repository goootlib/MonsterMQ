<?php

namespace MonsterMQ\Client;

use MonsterMQ\AMQPDispatchers\ChannelDispatcher;
use MonsterMQ\AMQPDispatchers\ConnectionDispatcher;
use MonsterMQ\AMQPDispatchers\ExchangeDispatcher;
use MonsterMQ\AMQPDispatchers\QueueDispatcher;
use MonsterMQ\AMQPDispatchers\TransactionDispatcher;
use MonsterMQ\Connections\BinaryTransmitter;
use MonsterMQ\Connections\Stream;
use MonsterMQ\Core\Channel;
use MonsterMQ\Core\Events;
use MonsterMQ\Core\Exchange;
use MonsterMQ\Core\Qos;
use MonsterMQ\Core\Session;
use MonsterMQ\Core\Queue;
use MonsterMQ\Core\Transaction;
use MonsterMQ\Interfaces\AMQPDispatchers\BasicDispatcher;
use MonsterMQ\Interfaces\Connections\BinaryTransmitter as BinaryTransmitterInterface;
use MonsterMQ\Interfaces\Core\Channel as ChannelInterface;
use MonsterMQ\Interfaces\Connections\Stream as StreamInterface;
use MonsterMQ\Interfaces\Core\Events as EventsInterface;
use MonsterMQ\Interfaces\Core\Exchange as ExchangeInterface;
use MonsterMQ\Interfaces\Core\Qos as QosInterface;
use MonsterMQ\Interfaces\Core\Queue as QueueInterface;
use MonsterMQ\Interfaces\Core\Session as SessionInterface;
use MonsterMQ\Interfaces\Core\Transaction as TransactionInterface;
use MonsterMQ\Interfaces\Support\Logger as LoggerInterface;
use MonsterMQ\Support\Logger;

/**
 * This is a parent class for both the producer and the consumer. It provides
 * common client functionality which producer and consumer extends.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
abstract class BaseClient
{
    /**
     * Logger instance responsible for writing log files and outputting to cli.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Instance of socket layer. Responsible for establishing TCP and TLS
     * connections.
     *
     * @var StreamInterface
     */
    protected $socket;

    /**
     * Events instance which is responsible for handling of connections closures
     * and suspensions.
     *
     * @var EventsInterface
     */
    protected $events;

    /**
     * Binary transmitter instance. Responsible for encoding to and decoding from
     * a binary string.
     *
     * @var BinaryTransmitterInterface
     */
    protected $transmitter;

    /**
     * Session module instance. Responsible for configuring and establishing of
     * AMQP session.
     *
     * @var SessionInterface
     */
    protected $session;

    /**
     * Instance responsible for opening and closing of AMQP channels.
     *
     * @var ChannelInterface
     */
    protected $channel;

    /**
     * Instance responsible for exchange declarations, bindings and deleting.
     *
     * @var ExchangeInterface
     */
    protected $exchange;

    /**
     * Instance responsible for managing queues.
     *
     * @var QueueInterface
     */
    protected $queue;

    /**
     * Instance which provides quality of service control.
     *
     * @var QosInterface
     */
    protected $qos;

    /**
     * This instance provides Basic AMQP-class methods. Which may be producer or
     * consumer specific.
     *
     * @var BasicDispatcher
     */
    protected $basicDispatcher;

    /**
     * Instance responsible for transactions feature.
     *
     * @var TransactionInterface
     */
    protected $transaction;

    /**
     * Channel number currently being used.
     *
     * @var int
     */
    protected $currentChannelNumber = 0;

    /**
     * BaseClient constructor.
     *
     * @param LoggerInterface|null $logger
     * @param EventsInterface|null $events
     * @param StreamInterface|null $socket
     * @param BinaryTransmitterInterface|null $transmitter
     * @param SessionInterface|null $session
     * @param ChannelInterface|null $channel
     * @param ExchangeInterface|null $exchange
     * @param QueueInterface|null $queue
     * @param QosInterface|null $qos
     * @param BasicDispatcher|null $basicDispatcher
     * @param TransactionInterface|null $transaction
     */
    public function __construct(
        LoggerInterface $logger = null,
        EventsInterface $events = null,
        StreamInterface $socket = null,
        BinaryTransmitterInterface $transmitter = null,
        SessionInterface $session = null,
        ChannelInterface $channel = null,
        ExchangeInterface $exchange = null,
        QueueInterface $queue = null,
        QosInterface $qos = null,
        BasicDispatcher $basicDispatcher = null,
        TransactionInterface $transaction = null
    ) {
        $this->setLogger($logger);

        $this->setEvents($events);
		
        $this->setSocket($socket);

        $this->setTransmitter($transmitter);

        $this->setSession($session);

        $this->setChannel($channel);

        $this->setExchange($exchange);

        $this->setQueue($queue);

        $this->basicDispatcher = $basicDispatcher ?? new \MonsterMQ\AMQPDispatchers\BasicDispatcher($this->transmitter, $this, $this->events);

        $this->setQos($qos);

        $this->setTransaction($transaction);
    }

    /**
     * Insert empty line to log file upon the session end.
     */
    public function __destruct()
    {
        //Separator of log message blocks in log file
        $this->logger->write("\n\n", true);
    }

    /**
     * Sets logger instance. Which is used almost by all library modules.
     *
     * @param LoggerInterface|null $logger
     *
     * @throws \Exception
     */
    protected function setLogger(LoggerInterface $logger = null)
    {
        if (!is_null($logger)) {
            $this->logger = $logger;
        } else {
            $this->logger = new Logger();
        }
    }

    /**
     * Sets the event instance. Which is used by all dispatchers.
     *
     * @param EventsInterface|null $events
     */
    protected function setEvents(EventsInterface $events = null)
    {
        if (!is_null($events)) {
            $this->events = $events;
        } else {
            $this->events = new Events();
        }
    }

    /**
     * Sets instance of socket abstraction layer.
     *
     * @param StreamInterface|null $socket
     */
    protected function setSocket(StreamInterface $socket = null)
    {
        if (is_null($socket)) {
            $this->socket = new Stream($this->logger);
        } else {
            $this->socket = $socket;
        }
    }

    /**
     * Sets binary transmitter instance. Which used by all AMQP dispatchers for transmitting
     * encoded data.
     *
     * @param BinaryTransmitterInterface|null $transmitter
     */
    protected function setTransmitter(BinaryTransmitterInterface $transmitter = null)
    {
        if (is_null($transmitter)) {
            $this->transmitter = new BinaryTransmitter($this->socket);
        } else {
            $this->transmitter = $transmitter;
        }
    }

    /**
     * Sets AMQP session layer instance.
     *
     * @param SessionInterface|null $session
     */
    protected function setSession(SessionInterface $session = null)
    {
        if (is_null($session)) {
            $this->session = new Session(new ConnectionDispatcher($this->transmitter, $this, $this->events), $this->logger);
        } else {
            $this->session = $session;
        }
    }

    /**
     * Sets AMQP channel manager.
     *
     * @param ChannelInterface|null $channel
     */
    protected function setChannel(ChannelInterface $channel = null)
    {
        if (is_null($channel)) {
            $this->channel = new Channel(new ChannelDispatcher($this->transmitter, $this, $this->events), $this->session, $this->logger);
        }else{
            $this->channel = $channel;
        }
    }

    /**
     * Sets exchange manager.
     *
     * @param ExchangeInterface|null $exchange
     */
    protected function setExchange(ExchangeInterface $exchange = null)
    {
        if (!is_null($exchange)) {
            $this->exchange = $exchange;
        } else {
            $this->exchange = new Exchange(new ExchangeDispatcher($this->transmitter, $this, $this->events), $this, $this->logger);
        }
    }

    /**
     * Sets queue manager.
     *
     * @param QueueInterface|null $queue
     */
    protected function setQueue(QueueInterface $queue = null)
    {
        if (!is_null($queue)) {
            $this->queue = $queue;
        } else {
            $this->queue = new Queue(new QueueDispatcher($this->transmitter, $this, $this->events), $this, $this->logger);
        }
    }

    /**
     * Sets instance responsible for quality of service.
     *
     * @param QosInterface|null $qos
     */
    protected function setQos(QosInterface $qos = null)
    {
        if (!is_null($qos)) {
            $this->qos = $qos;
        } else {
            $this->qos = new Qos($this->basicDispatcher, $this, $this->logger);
        }
    }

    /**
     * Sets transaction manager.
     *
     * @param TransactionInterface|null $transaction
     */
    protected function setTransaction(TransactionInterface $transaction = null)
    {
        if (!is_null($transaction)) {
            $this->transaction = $transaction;
        } else {
            $this->transaction = new Transaction(new TransactionDispatcher($this->transmitter, $this, $this->events), $this, $this->logger);
        }
    }

    /**
     * Connects to AMQP server.
     *
     * @param string   $address           Server's IP addres.
     * @param int      $port              Server's port number.
     * @param int|null $connectionTimeout Timeout after which connection attempt
     *                                    will be aborted.
     */
    public function connect(string $address = '127.0.0.1', int $port = 5672, int $connectionTimeout = null)
    {
        $this->socket->connect($address, $port, $connectionTimeout);
    }

    /**
     * Starts AMQP session.
     *
     * @param string $username Username for RabbitMQ user.
     * @param string $password Password for RabbitMQ user.
     *
     * @throws \MonsterMQ\Exceptions\PackerException
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function logIn(string $username = 'guest', string $password = 'guest')
    {
        if (!$this->socket->isConnected()){
            $this->socket->connect();
        }

        $this->session->logIn($username, $password);

        $this->logger()->write('Session established');

        $this->changeChannel();
    }

    /**
     * Changes channel currently being used.
     *
     * @param null $channel Might be used to specify concrete channel number to
     *                      change.
     *
     * @return bool|null     Returns channel number that was selected or false
     *                       if channel is closed or suspended.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function changeChannel($channel = null)
    {
        if (!is_null($channel)) {
            if(in_array($channel, ChannelDispatcher::$closedChannels)
                || in_array($channel, ChannelDispatcher::$suspendedChannels)){
                return false;
            } elseif (in_array($channel, ChannelDispatcher::$openedChannels)) {
                return $this->currentChannelNumber = $channel;
            }
        }

        $channel = $this->channel->open($channel);
        $this->currentChannelNumber = $channel;
        $this->logger()->write('Channel changed to '.$channel);
        return $channel;
    }

    /**
     * Close specified channel.
     *
     * @param int $channel Channel to close.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function closeChannel(int $channel)
    {
        $this->channel->close($channel);
        $this->logger()->write("Channel {$channel} closed");
    }

    /**
     * Returns channel number currently being used.
     *
     * @return int
     */
    public function currentChannel()
    {
        return $this->currentChannelNumber;
    }

    /**
     * Declares new direct exchange.
     *
     * @return \MonsterMQ\Interfaces\Core\Exchange
     */
    public function newDirectExchange(string $name)
    {
        $this->exchange->setCurrentExchangeName($name);
        $this->exchange->setExchangeType('direct');
        return $this->exchange;
    }

    /**
     * Declares new fanout exchange.
     *
     * @return \MonsterMQ\Interfaces\Core\Exchange
     */
    public function newFanoutExchange(string $name)
    {
        $this->exchange->setCurrentExchangeName($name);
        $this->exchange->setExchangeType('fanout');
        return $this->exchange;
    }

    /**
     * Declares new topic exchange.
     *
     * @return \MonsterMQ\Interfaces\Core\Exchange
     */
    public function newTopicExchange(string $name)
    {
        $this->exchange->setCurrentExchangeName($name);
        $this->exchange->setExchangeType('topic');
        return $this->exchange;
    }

    /**
     * Closes opened session and network connection.
     */
    public function disconnect()
    {
        $this->session()->logOut();
        $this->socket()->close();
    }

    /**
     * Returns logger instance.
     *
     * @return \MonsterMQ\Interfaces\Support\Logger
     */
    protected function logger()
    {
        return $this->logger;
    }

    /**
     * Returns network abstraction layer instance.
     *
     * @return \MonsterMQ\Interfaces\Connections\Stream
     */
    public function socket()
    {
        return $this->socket;
    }

    /**
     * Returns network abstraction layer instance.
     *
     * @return \MonsterMQ\Interfaces\Connections\Stream
     */
    public function network()
    {
        return $this->socket;
    }

    /**
     * Returns session instance.
     *
     * @return \MonsterMQ\Interfaces\Core\Session
     */
    public function session()
    {
        return $this->session;
    }

    /**
     * Returns channel manager.
     *
     * @return \MonsterMQ\Interfaces\Core\Channel
     */
    public function channel()
    {
        return $this->channel;
    }

    /**
     * Returns exchange manager.
     *
     * @param string $name Exchange to manage.
     *
     * @return \MonsterMQ\Interfaces\Core\Exchange
     */
    public function exchange(string $name)
    {
        $this->exchange->setCurrentExchangeName($name);
        return $this->exchange;
    }

    /**
     * Returns queue manager.
     *
     * @param string $queueName Queue to manage.
     *
     * @return \MonsterMQ\Interfaces\Core\Queue
     */
    public function queue(string $queueName)
    {
        $this->queue->setCurrentQueueName($queueName);
        return $this->queue;
    }

    /**
     * Returns quality of service manager.
     *
     * @return \MonsterMQ\Interfaces\Core\Qos
     */
    public function qos()
    {
        return $this->qos;
    }

    /**
     * Returns transaction manager
     *
     * @return \MonsterMQ\Interfaces\Core\Transaction
     */
    public function transaction()
    {
        return $this->transaction;
    }

    /**
     * Returns event manager.
     *
     * @return \MonsterMQ\Interfaces\Core\Events
     */
    public function events()
    {
        return $this->events;
    }
}