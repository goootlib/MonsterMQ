<?php


namespace MonsterMQ\Core;

use MonsterMQ\Core\AuthenticationStrategies\AMQPLAINStrategy;
use MonsterMQ\Core\AuthenticationStrategies\PLAINStrategy;
use MonsterMQ\Exceptions\PackerException;
use MonsterMQ\Exceptions\ProtocolException;
use MonsterMQ\Interfaces\AMQPDispatchers\ConnectionDispatcher as ConnectionDispatcherInterface;
use MonsterMQ\Interfaces\Core\Session as SessionInterface;

/**
 * This class responsible for session opening.
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
     * Whether AMQP connection is established.
     *
     * @var
     */
    public $loggedIn;

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
     * Connection constructor.
     *
     * @param ConnectionDispatcherInterface $dispatcher
     */
    public function __construct(ConnectionDispatcherInterface $dispatcher)
    {
        $this->connectionDispatcher = $dispatcher;
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
		if ($this->loggedIn) {
			return;
		}
		
        $this->startSession($username, $password);

        $this->tuneSession();

        $this->openVirtualHost();

        $this->loggedIn = true;
    }

    public function logOut()
    {

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
        } elseif (in_array('PLAIN', $securityMechanisms)) {
            $this->connectionDispatcher->authStrategy = new PLAINStrategy();
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

        $this->selectAuthStrategy($properties['mechanisms']);
		
		$locales = explode(' ', $properties['locales']);
		if (in_array($this->locale, $locales)) {
			$locale = $this->locale;
		}else{
			$locale = 'en_US';
		}
        $this->connectionDispatcher->sendStartOk($username, $password, $locale);
    }

    /**
     * Tunes session.
     */
    protected function tuneSession()
    {
        $properties = $this->connectionDispatcher->receiveTune();
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
}