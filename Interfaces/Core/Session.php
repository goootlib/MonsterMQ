<?php


namespace MonsterMQ\Interfaces\Core;

use MonsterMQ\Exceptions\ProtocolException;

interface Session
{

    /**
     * Opens new AMQP layer connection, authenticates user, and adjust some
     * session settings.
     *
     * @param string $username Given username for authentication.
     * @param string $password Given password for authentication.
     *
     * @throws ProtocolException
     * @throws \MonsterMQ\Exceptions\PackerException
     */
    public function logIn(string $username = 'guest', string $password = 'guest');

    public function logOut();

    /**
     * This method allows to choose a locale for a session.
     *
     * @param string $locale Locale name to be used.
     *
     * @return \MonsterMQ\Core\Session For chaining purposes.
     */
    public function locale(string $locale);

    /**
     * This method allows to choose virtual host to be opened.
     *
     * @param string $path Virtual host path to be opened.
     *
     * @return \MonsterMQ\Core\Session For chaining purposes.
     */
    public function virtualHost(string $path);

}