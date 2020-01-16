<?php


namespace MonsterMQ\Core;

use MonsterMQ\Core\AuthenticationStrategies\AMQPLAINStrategy;
use MonsterMQ\Core\AuthenticationStrategies\PLAINStrategy;
use MonsterMQ\Exceptions\PackerException;
use MonsterMQ\Exceptions\ProtocolException;
use MonsterMQ\Interfaces\AMQPDispatchers\ConnectionDispatcher as ConnectionDispatcherInterface;
use MonsterMQ\Interfaces\Core\Session as SessionInterface;
use MonsterMQ\Interfaces\Support\Logger as LoggerInterface;

/**
 * This class responsible for session processing.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class Session implements SessionInterface
{
    /**
     * Connection dispatcher instance.
     *
     * @var ConnectionDispatcherInterface
     */
    protected $connectionDispatcher;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Virtual host to be opened.
     *
     * @var string
     */
    protected $virtualHost = "/";

    /**
     * Locale that will be used.
     *
     * @var string
     */
    protected $locale = 'en_US';

    /**
     * Negotiated with server maximum channel number. Negotiation occurs by
     * sending Connection.TuneOk method.
     *
     * @var int
     */
    protected $channelMaxNumber;

    /**
     * Negotiated with server maximum frame size. Negotiation occurs by sending
     * Connection.TuneOk method.
     *
     * @var int
     */
    protected $frameMaxSize;

    /**
     * Negotiated with server heartbeat interval. Negotiation occurs by sending
     * Connection.TuneOk method.
     *
     * @var int
     */
    protected $heartbeatInterval;

    /**
     * Connection constructor.
     *
     * @param ConnectionDispatcherInterface $dispatcher
     */
    public function __construct(ConnectionDispatcherInterface $dispatcher, LoggerInterface $logger)
    {
        $this->connectionDispatcher = $dispatcher;
        $this->logger = $logger;
    }

    /**
     * Opens new AMQP layer connection, authenticates user, and adjust some
     * session settings.
     *
     * @param string $username Given username for authentication.
     * @param string $password Given password for authentication.
     *
     * @throws PackerException
     * @throws ProtocolException
     */
    public function logIn(string $username = 'guest', string $password = 'guest')
    {
        $this->startSession($username, $password);

        $this->tuneSession();

        $this->openVirtualHost();
    }

    /**
     * Terminates current session.
     *
     * @throws ProtocolException
     * @throws \MonsterMQ\Exceptions\ConnectionException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function logOut()
    {
        $this->connectionDispatcher->sendClose();
        $this->connectionDispatcher->receiveCloseOK();
    }

    /**
     * Selects authentication strategy which is used during connection
     * establishment.
     *
     * @param string $securityMechanisms List of authentication mechanisms
     *                                   supported by server.
     *
     * @throws ProtocolException
     */
    protected function selectAuthStrategy(string $securityMechanisms)
    {
        $securityMechanisms = explode(' ', $securityMechanisms);

        if (in_array('AMQPLAIN',$securityMechanisms)) {
            $this->connectionDispatcher->authStrategy = new AMQPLAINStrategy();
            return "AMQPLAIN";
        } elseif (in_array('PLAIN', $securityMechanisms)) {
            $this->connectionDispatcher->authStrategy = new PLAINStrategy();
            return "PLAIN";
        } else {
            throw new ProtocolException(
                'No supported authentication method. MonsterMQ supports only
                PLAIN and AMQPLAIN.'
            );
        }
    }

    /**
     * Starts session and adjust some properties.
     *
     * @param string $username Username to be used for authentication.
     * @param string $password Password to be used for authentication.
     *
     * @throws PackerException
     * @throws ProtocolException
     */
    protected function startSession(string $username, string $password)
    {
        $properties = $this->connectionDispatcher->receiveStart();

        $mechanism = $this->selectAuthStrategy($properties['mechanisms']);
		
		$locales = explode(' ', $properties['locales']);
		if (in_array($this->locale, $locales)) {
			$locale = $this->locale;
		}else{
			$locale = 'en_US';
		}

		$this->logger->write(
		    "Starting session with {$mechanism} auth mechanism and 
		    {$locale} locale"
        );

        $this->connectionDispatcher->sendStartOk($username, $password, $locale);
    }

    /**
     * Tunes session.
     */
    protected function tuneSession()
    {
        $properties = $this->connectionDispatcher->receiveTune();

        $this->channelMaxNumber = $properties['channelMaxNumber'];
        $this->frameMaxSize = $properties['frameMaxSize'];
        $this->heartbeatInterval = $properties['heartbeat'];

        $this->logger->write(
            "Starting to tune session with {$this->channelMaxNumber} channel max number, 
            {$this->frameMaxSize} frame max size and {$this->heartbeatInterval} heartbeat interval."
        );

        $this->connectionDispatcher->sendTuneOk(
            $properties['channelMaxNumber'],
            $properties['frameMaxSize'],
            $properties['heartbeat']
        );
    }

    /**
     * Opens virtual host.
     */
    protected function openVirtualHost()
    {
        $this->logger->write("Opening '{$this->virtualHost}' virtual host");
        $this->connectionDispatcher->sendOpen($this->virtualHost);
        $this->connectionDispatcher->receiveOpenOk();
    }

    /**
     * This method allows to choose virtual host to be opened.
     *
     * @param string $path Virtual host path to be opened.
     *
     * @return $this For chaining purposes.
     */
    public function virtualHost(string $path)
    {
        $this->virtualHost = $path;

        return $this;
    }

    /**
     * This method allows to choose a locale for a session.
     *
     * @param string $locale Locale name to be used.
     *
     * @return $this For chaining purposes.
     */
    public function locale(string $locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Returns negotiated with server maximum channel number.
     *
     * @return int Limit for channels that might be opened..
     */
    public function channelMaxNumber()
    {
        return $this->channelMaxNumber;
    }

    /**
     * Returns negotiated with server maximum frame size.
     *
     * @return int Limit for frame size.
     */
    public function frameMaxSize()
    {
        return $this->frameMaxSize;
    }

    /**
     * Returns negotiated with server heartbeat interval.
     *
     * @return int Heartbeat interval.
     */
    public function heartbeatInterval()
    {
        return $this->heartbeatInterval;
    }
}