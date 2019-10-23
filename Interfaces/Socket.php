<?php

namespace MonsterMQ\Interfaces;

Interface Socket
{
    /**
     * This method writes data to previously
     * configured socket connection.
     *
     * @param $data Data that will be transmitted
     * @return void
     */
    public function writeRaw($data);

    /**
     * This method reads from the established
     * socket connection.
     *
     * @param $bytes Number of bytes to be read
     * @return string Data received through connection
     */
    public function readRaw($bytes);
}