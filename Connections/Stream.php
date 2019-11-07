<?php

namespace MonsterMQ\Connections;

use http\Exception\InvalidArgumentException;
use MonsterMQ\Exceptions\NetworkException;
use MonsterMQ\Interfaces\Stream as StreamInterface;

/**
 * Class Stream based on capabilities
 * of php core extension, which provides low-level
 * interface for communicating between network peers.
 *
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class Stream implements StreamInterface
{
    /**
     * OpenSSL's C library function SSL_write() can balk on buffers > 8192
     * bytes in length, so we're limiting the write size by this constant. On both TLS
     * and plaintext connections, the writing will not exceed 8192 bytes at a time.
     */
    const WRITE_BUFFER_SIZE = 8192;

    /**
     * Created stream context.
     *
     * @var resource
     */
    protected $context;

    /**
     * Opened stream resource.
     *
     * @var resource
     */
    protected $streamResource;

    /**
     * Protocol to be used.
     *
     * @var string
     */
    protected $protocol = 'tcp';

    /**
     * Address that will be used for binding. If stays 0, php selects ip address by itself.
     *
     * @var int
     */
    protected $bindAdress = 0;

    /**
     * Port that will be used for binding. If stays 0, php selects it by itself.
     *
     * @var int
     */
    protected $bindPort = 0;

    /**
     * Time after which reading from or writing to socket fails. First element indicating seconds,
     * second - microseconds.
     *
     * @var array
     */
    protected $timeout = [130, 0];

    /**
     * Whether Nagle's algorithm enabled or not. False means enabled.
     *
     * @var bool
     */
    protected $tcpNodelay = false;

    /**
     * Whether keepalive enabled or not.
     *
     * @var bool
     */
    protected $keepaliveEnabled = true;

    /**
     * Whether stream context was created or not.
     *
     * @var bool
     */
    protected $contextAvailable = false;

    /**
     * Close connection of object deleting.
     */
    public function __destruct()
    {
        $this->close();
    }


    /**
     * Sets reading/writing timeout after which reading
     * from or writing to socket fails.
     *
     * @param int|float $seconds     In case of int type of the first argument, the second argument also
     *                               must be set. In case of float type of first argument
     *                               fractional part of float number will be treated as microseconds and will
     *                               be used instead of second argument.
     * @param int|null $microseconds Defines microseconds part of reading
     *                               timeout.
     * @return $this                 For chaining purposes.
     */
    public function setTimeout($seconds, $microseconds = 0)
    {
        if(!is_int($seconds) && !is_float($seconds)){
            throw new InvalidArgumentException(
                'Error while setting reading timeout. Provided "seconds" argument is not an integer or a float.'
            );
        }elseif (is_int($seconds) && !is_int($microseconds)){
            throw new InvalidArgumentException(
                'Error while setting reading timeout. 
                If provided "seconds" argument is an integer you must provide second "microseconds" argument as integer too.'
            );
        }

        if(is_float($seconds)){
            //Fractional part of float multiplied by number of microseconds in one second.
            $microseconds = fmod($seconds,1) * 1000000;
            $seconds = floor($seconds);
        }
        $this->timeout = [$seconds, $microseconds];

        return $this;
    }

    /**
     * Which ip address and port will be used for binding.
     *
     * @param int $port    Port to be used for binding.
     * @param int $address IP address to be used for binding.
     * @return    $this    For chaining purposes.
     */
    public function bind($port = 0, $address = 0)
    {
        $this->bindPort = $port;
        $this->bindAddress = $address;

        return $this;
    }

    /**
     * Disables Nagle's algorithm.
     *
     * @return $this For chaining purposes.
     */
    public function enableNodelay()
    {
        //TCP nodelay available only in PHP 7.1 and higher.
        if(PHP_VERSION_ID >= 70100) {
            $this->tcpNodelay = true;
        }

        return $this;
    }

    /**
     * Whether TCP nodelay enabled or not.
     *
     * @return bool
     */
    public function nodelayEnabled()
    {
        //TCP nodelay available only in PHP 7.1 and higher.
        if(PHP_VERSION_ID >= 70100 && $this->tcpNodelay == true){
            return true;
        }
    }

    /**
     * Disables keepalive.
     *
     * @return $this For chaining purposes.
     */
    public function disableKeepalive()
    {
        $this->keepaliveEnabled = false;

        return $this;
    }

    /**
     * Recreates context resource.
     */
    protected function refreshContext()
    {
        $socketContextOptions = ['bindto' => "{$this->bindAdress}:{$this->bindPort}"];

        if($this->nodelayEnabled()){
            $socketContextOptions = array_merge($socketContextOptions, ['tcp_nodelay' => true]);
        }
        $this->context = stream_context_create([
            'socket' => $socketContextOptions
        ]);

        $this->contextAvailable = true;
    }

    /**
     * Sets reading/writing timeout on opened stream resource.
     *
     * @return bool Whether timeout has been set or not.
     */
    protected function applyTimeout()
    {
        $seconds = $this->timeout[0];
        $microseconds = $this->timeout[1];

        return stream_set_timeout($this->streamResource, $seconds, $microseconds);
    }

    /**
     * Keepalive available only if "sockets" php extension available.
     *
     * @return bool Whether keepalive available or not.
     */
    public function keepaliveAvailable()
    {
        if(
            defined('SOL_SOCKET')
            && defined('SO_KEEPALIVE')
            && function_exists('socket_import_stream')
        ){
            return true;
        }
    }

    /**
     * Enables keepalive connection option.
     */
    protected function applyKeepalive()
    {
        if($this->keepaliveEnabled && $this->keepaliveAvailable()){
            $socket = socket_import_stream($this->streamResource);
            socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
        }
    }

    /**
     * Connects to specified address and port.
     *
     * @param string            $address           IP address which AMQP server listens.
     * @param int               $AMQPport          Port to which AMQP server was bound.
     * @param float             $connectionTimeout Time after which connection attempt fails.
     * @throws NetworkException                    Throws in case of connection could not be established.
     */
    public function connect ($address = '127.0.0.1', $AMQPport = 5672, $connectionTimeout = null)
    {
        if(!$this->contextAvailable){
            $this->refreshContext();
        }

        $this->streamResource = stream_socket_client(
            "{$this->protocol}://{$address}:{$AMQPport}",
            $errorCode,
            $errorMessage,
            $connectionTimeout,
            STREAM_CLIENT_CONNECT,
            $this->context
        );

        if($this->streamResource === false){
            throw new NetworkException(
                "Error during connection establishment. 
                Error code - {$errorCode}. Error message - {$errorMessage}.", $errorCode);
        }

        $this->applyTimeout();
        $this->applyKeepalive();


    }


    /**
     * Whether connection closed or not.
     *
     * @return bool
     */
    public function connectionClosed()
    {
        if(!$this->streamResource){
            return true;
        }
    }

    /**
     * Writes to the socket.
     *
     * @param string            $data Data to be sent.
     * @return int|void               Amount of data has been written.
     * @throws NetworkException       In case of closure connection or writing error.
     */
    public function writeRaw($data)
    {
        if($this->connectionClosed()){
            throw new NetworkException('Connection was closed.');
        }

        $dataLength = strlen($data);
        $written = 0;
        while ($written < $dataLength){
            $result = fwrite($this->streamResource, $data, static::WRITE_BUFFER_SIZE);

            if($result === false){
                throw new NetworkException('Error sending data.');
            }

            $written += $result;
        }

        return $written;
    }

    /**
     * Reads from the socket.
     *
     * @param Number            $bytes Number of bytes to be read.
     * @return string                  Data received from remote peer.
     * @throws NetworkException        In case of connection closure or reading error.
     */
    public function readRaw($bytes)
    {
        if($this->connectionClosed()){
            throw new NetworkException('Connection was closed.');
        }
        return fread($this->streamResource, $bytes);
    }

    /**
     * Closes network connection.
     *
     * Before closing network connections don't
     * forget to send Connection.Close method to the server. Or hand-shake
     * incoming Connection.Close with Connection.CloseOk .
     */
    public function close()
    {
        stream_socket_shutdown($this->streamResource, STREAM_SHUT_RDWR);
    }
}