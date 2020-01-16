<?php


namespace MonsterMQ\Interfaces\Support;


interface Logger
{
    /**
     * Writes process description message to log file or outputs to cli.
     *
     * @param string $message Message to write.
     */
    public function write(string $message);
}