<?php

namespace MonsterMQ\Interfaces\Core;

interface Qos
{
    /**
     * Sets number of messages which may be sent in advance.
     *
     * @param int $count
     *
     * @return \MonsterMQ\Core\Qos
     */
    public function prefetchCount(int $count = 0);

    /**
     * Enables qos per consumer.
     *
     * @return \MonsterMQ\Core\Qos
     */
    public function perConsumer();

    /**
     * Enables qos per channel.
     *
     * @return \MonsterMQ\Core\Qos
     */
    public function perChannel();

    /**
     * Applies qos settings.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function apply();
}