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
    public function writeRaw ($data);

    /**
     * This method reads from the established
     * network connection.
     *
     * @param $bytes Number of bytes to be read
     * @return string Data received through connection
     */
    public function readRaw ($bytes);

    /**
     * Opens the network connection to specified address
     * and port.
     * @param string $address
     * @param int $AMQPport
     * @return mixed
     */
    public function connect ($address = '127.0.0.1', $AMQPport = 5672);

    /**
     * Closes current network connection.
     * @return mixed
     */
    public function close();
}