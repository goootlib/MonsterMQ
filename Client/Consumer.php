<?php

namespace MonsterMQ\Client;

class Consumer extends BaseClient
{
    public function wait(\Closure $handler)
    {
        $arguments = $this->basicDispatcher->receiveMessage();
        call_user_func_array($handler, $arguments);
    }
}