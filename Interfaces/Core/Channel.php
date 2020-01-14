<?php


namespace MonsterMQ\Interfaces\Core;


interface Channel
{
    /**
     * Open a channel for use.
     *
     * @param $channel Channel to open.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function open($channel);

    /**
     * Requests server to reenable flow on specified channel.
     *
     * @param int $channel Channel to reenable.
     *
     * @return bool Whether flow was enabled.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function continue(int $channel): bool;

    /**
     * Closes selected channel.
     *
     * @param int $channel Channel to close.
     *
     * @throws \MonsterMQ\Exceptions\ProtocolException
     * @throws \MonsterMQ\Exceptions\SessionException
     */
    public function close(int $channel);
}