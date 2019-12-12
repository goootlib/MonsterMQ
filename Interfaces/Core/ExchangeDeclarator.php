<?php


namespace MonsterMQ\Interfaces\Core;


interface ExchangeDeclarator
{
    /**
     * Declares exchanges with currently set arguments.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function declare();

    /**
     * Sets exchange name for current declaration.
     *
     * @param string $name Exchange name to be declared.
     */
    public function setName(string $name);

    /**
     * Sets exchange type for current declaration. Supported types are
     * "direct, fanout, topic".
     *
     * @param string $type Exchange type to be declared.
     */
    public function setType(string $type = 'direct');

    /**
     * Sets exchange durable. Durable exchanges remains after server restart.
     */
    public function setDurable();

    /**
     * Sets exchange autodelete. Autodelete exchanges delete if no consumers
     * left.
     */
    public function setAutodelete();
}