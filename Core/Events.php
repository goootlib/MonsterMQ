<?php

namespace MonsterMQ\Core;

use MonsterMQ\Interfaces\Core\Events as EventsInterface;

class Events implements EventsInterface
{
    /**
     * Callbacks which will be executed on channel closure.
     *
     * @var array
     */
    protected $closureHandlers = [];

    /**
     * Callbacks which will be executed on channel suspension.
     *
     * @var array
     */
    protected $suspensionHandlers = [];

    /**
     * Adds channel closure handler.
     *
     * @param \Closure $handler Channel closure handler.
     *
     * @return $this
     */
    public function channelClosure(\Closure $handler)
    {
        $this->closureHandlers[] = $handler;

        return $this;
    }

    /**
     * Adds channel suspension handler.
     *
     * @param \Closure $handler Channel suspension handler.
     *
     * @return $this
     */
    public function channelSuspension(\Closure $handler)
    {
        $this->suspensionHandlers[] = $handler;

        return $this;
    }

    /**
     * Run closure callbacks on channel closure.
     *
     * @param int $channel Handler argument representing channel number.
     */
    public function runClosureHandlers(int $channel)
    {
        if (empty($this->closureHandlers)) {
            return;
        }

        foreach ($this->closureHandlers as $handler) {
            call_user_func($handler, $channel);
        }
    }

    /**
     * Run suspension callbacks on channel suspension.
     *
     * @param int $channel Handler argument representing channel number.
     */
    public function runSuspensionHandlers(int $channel)
    {
        if (empty($this->suspensionHandlers)) {
            return;
        }

        foreach ($this->suspensionHandlers as $handler) {
            call_user_func($handler, $channel);
        }
    }
}