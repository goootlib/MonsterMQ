<?php


namespace MonsterMQ\Interfaces\Core;


interface Events
{
    /**
     * Adds channel closure handler.
     *
     * @param \Closure $handler Channel closure handler.
     *
     * @return \MonsterMQ\Interfaces\Core\Events
     */
    public function channelClosure(\Closure $handler);

    /**
     * Adds channel suspension handler.
     *
     * @param \Closure $handler Channel suspension handler.
     *
     * @return \MonsterMQ\Interfaces\Core\Events
     */
    public function channelSuspension(\Closure $handler);
}