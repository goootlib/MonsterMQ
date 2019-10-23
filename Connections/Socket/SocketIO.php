<?php

namespace MonsterMQ\Connections\IO;

use http\Exception\InvalidArgumentException;
use MonsterMQ\Exceptions\SocketException;
use MonsterMQ\Interfaces\Socket as SocketInterface;

/**
 * Class SocketIO based on capabilities
 * of sockets php extension, which provides low-level
 * interface for communicating between peers.
 * @author Gleb Zhukov <goootlib@gmail.com>
 */
class SocketIO implements SocketInterface
{
    /**
     * Resource required for socket functions.
     * @var resource
     */
    protected $resource;

    /**
     * Time after which reading from socket
     * fails.
     * @var int
     */
    protected $readTimeout = ['sec' => 130,'usec' => 0];

    /**
     * Time after which writing to socket
     * fails.
     * @var int
     */
    protected $writeTimout = ['sec' => 130,'usec' => 0];

    /**
     * Indicates whether to use IPv6 or not.
     * @var bool
     */
    protected $useIPv6 = false;

    /**
     * Indicates whether to use unix sockets,
     * in case the connection will be established
     * between two local processes.
     * @var bool
     */
    protected $useUnixSockets = false;

    /**
     * Whether to use UDP transport.
     * False means use TCP.
     * @var bool
     */
    protected $useUDPTransport = false;

    /**
     * Whether read, write, connect etc. operations
     * would block process execution.
     * @var bool
     */
    protected $nonBlocking = false;

    /**
     * Whether socket resource was bound
     * to some interface or not.
     * @var bool
     */
    protected $isBound = false;


    public function __construct()
    {
        if(!$this->socketsModuleIsAvailable()){
            throw new SocketException(
                'Sockets php extension is not installed or had not been loaded.
                 You may use MonsterMQ\\Connections\\Socket\\StreamIO instead, 
                 which uses core php extension for connections.'
            );
        }

        //We try to bind socket and establish connection with default settings
        //on transmitter creation in order to user have not to do it manually after.
        //In case of using non-default options you have to invoke Transmitter::bind()
        //and Transmitter::connect() methods manually after.
        $isBound = $this->bind('127.0.0.1',null,false);

        if($isBound !== false) {
            $this->open();
        }
    }

    /**
     * Checks whether Sockets module is available
     * for using.
     * @return bool Available or not.
     */
    public function socketsModuleIsAvailable ()
    {
        return extension_loaded('Sockets');
    }

    /**
     * Enables UDP transport for connection.
     * @return $this For chaining purposes.
     */
    public function useUDP()
    {
        $this->useUDPTransport = true;

        return $this;
    }

    /**
     * Enables IPv6.
     * @return $this For chaining purposes.
     */
    public function useIPv6()
    {
        $this->useIPv6 = true;

        return $this;
    }

    /**
     * Enables Unix sockets. Should be used
     * in case of local communication. High efficiency
     * and low overhead make it a great form of IPC
     * (Interprocess Communication).
     * @return $this For chaining puposes.
     */
    public function useUnixSockets()
    {
        $this->useUnixSockets = true;

        return $this;
    }

    /**
     * Disables execution blocking on
     * read, write, connect etc. operations.
     * @return $this For chaining purposes.
     */
    public function enableNonBlockingMode()
    {
        $this->nonBlocking = true;

        return $this;
    }


    /**
     * Sets reading timeout after which reading
     * from socket fails.
     * @param int|float $sec In case of int type
     * second $usec argument also must be set. In case
     * of float, fractional part of float number will be treated
     * as microseconds and will be used instead of second argument.
     * @param int|null $usec Defines microseconds part of reading
     * timeout.
     */
    public function setReadingTimeout($sec, $usec = 0)
    {
        if(!is_int($sec) && !is_float($sec)){
            throw new InvalidArgumentException(
                'Error while setting reading timeout. Provided "sec" argument is not an integer or a float.'
            );
        }elseif (is_int($sec) && !is_int($usec)){
            throw new InvalidArgumentException(
                'Error while setting reading timeout. 
                If provided "sec" argument is an integer you must provide second "usec" argument as integer too 
                (which defines number of microseconds).'
            );
        }

        if(is_float($sec)){
            //Fractional part of float multiplied by number of microseconds in second.
            $usec = fmod($sec,1) * 1000000;
            $sec = floor($sec);
        }
        $this->readTimeout = ['sec'=>$sec,'usec'=>$usec];
    }


    /**
     * Sets writing timeout after which writing
     * to socket fails.
     * @param int|float $sec In case of int type
     * second $usec argument also must be set. In case
     * of float, fractional part of float number will be treated
     * as microseconds and will be used instead of second argument.
     * @param int|null $usec Defines microseconds part of reading
     * timeout.
     */
    public function setWritingTimeout($sec, $usec = 0)
    {
        if(!is_int($sec) && !is_float($usec)){
            throw new InvalidArgumentException(
                'Error while setting writing timeout. Provided "sec" argument is not an integer or a float.'
            );
        }elseif (is_int($sec) && !is_int($usec)){
            throw new InvalidArgumentException(
                'Error while setting reading timeout. 
                If provided "sec" argument is an integer you must provide second "usec" argument as integer too 
                (which defines number of microseconds).'
            );
        }

        if(is_float($sec)){
            //Fractional part of float multiplied by number of microseconds in second.
            $usec = fmod($sec,1) * 1000000;
            $sec = floor($sec);
        }
        $this->writeTimout = ['sec'=>$sec,'usec'=>$usec];
    }
    /**
     * Whether IPv4 going to be used for connection.
     * @return bool IPv4 enabled or not.
     */
    public function IPv4enabled(){
        return $this->useUnixSockets == false && $this->useIPv6 == false;
    }

    /**
     * Returns value of domain argument for socket_create()
     * function.
     * @return int socket_create() domain argument.
     */
    protected function getSocketDomain ()
    {
        if($this->IPv4enabled()){
            $domain = AF_INET;
        }else {
            //In case of both domains are enabled. Unix sockets will take precedence.
            $domain = $this->useUnixSockets == false && $this->useIPv6 == true ? AF_INET6 : AF_UNIX;
        }

        return $domain;
    }

    /**
     * Determine transport argument for socket_create()
     * function.
     * @return int socket_create() transport argument.
     */
    protected function getSocketTransport()
    {
        // 0 is the value for unix sockets transport type
        return $this->useUDPTransport ? SOL_UDP : $this->useUnixSockets ? 0 : SOL_TCP;
    }



    /**
     * Recreates socket resource.
     * @throws SocketException If resource can not
     *  be created.
     */
    protected function refreshResource()
    {
        $this->resource = socket_create(
            $this->getSocketDomain(),
            SOCK_STREAM,
            $this->getSocketTransport()
        );

        if ($this->resource === false) {
            $this->throwSocketException('Can not to create a socket connection resource.');
        }

        $this->configureSocketOptions();
    }

    /**
     * Configures socket.
     */
    protected function configureSocketOptions()
    {
        //Enable debugging
        socket_set_option($this->resource, SOL_SOCKET, SO_DEBUG, 1);

        //Set reading/writing timeouts
        socket_set_option($this->resource,SOL_SOCKET,SO_RCVTIMEO, $this->readTimeout);
        socket_set_option($this->resource, SOL_SOCKET, SO_SNDTIMEO, $this->writeTimout);

        //Set keepalive option if we use tcp transport
        if ($this->useUDPTransport == false) {
            socket_set_option($this->resource, SOL_SOCKET, SO_KEEPALIVE, 1);
        }

        //Enables/Disables blocking mode
        if ($this->nonBlocking == false){
            socket_set_block($this->resource);
        }else{
            socket_set_nonblock($this->resource);
        }
    }

    /**
     * Binds socket resource to specified address and port.
     * Keep in mind that this function must be called after
     * connection configuration functions like Transmitter::useUDP(),
     * Transmitter::useIPv6(),Transmitter::useUnixSockets(),
     * Transmitter::enableNonBlocking() and setter methods
     * of reading/writing timeouts.
     * @param string $address   May be IPv4, IPv6 or socket file.
     * @param null $port    May be any valid, not used port number.
     *  If not specified system will choose random, free port number
     *  by itself.
     * @param bool $throwOnFailure   Whether to throw exception or return false on
     *  failure.
     * @return bool|$this   For chaining purposes. Or may be false when can not
     *  bind to specified address.
     * @throws SocketException May be thrown when can not bind
     *  specified address.
     */
    public function bind ($address = '127.0.0.1', $port = null, $throwOnFailure = true)
    {
        $this->refreshResource();
        //Require write access to this file
        if($this->useUnixSockets == true && !file_exists($address)){
            $address = '~/tmp/sockets/monstermq.sock';
        }

        $result = socket_bind($this->resource, $address, $port);

        if($result === false && $throwOnFailure === true){
            $this->throwSocketException(
                'Can not bind socket to specified address '.$address.' '.$port.'.'
            );
        }elseif ($result === false){
            return false;
        }

        $this->isBound = true;

        return $this;
    }

    /**
     * Connect to specified address and port.
     * @param string $address IP address which AMQP server listens
     *  or socket file to connect through.
     * @param int $AMQPport Port to which AMQP server was bound.
     * @throws SocketException Throws in case of connection
     *  could not be established.
     */
    public function open ($address = '127.0.0.1', $AMQPport = 5672)
    {
        if(!$this->isBound){
            throw new SocketException(
                'Connection try without preceding socket binding.
                Bind socket with SocketTransmitter::bind() method
                before trying to connect.'
            );
        }

        if($this->useUnixSockets){
            $AMQPport = null;
        }

        $result = socket_connect($this->resource, $address, $AMQPport);

        if($result === false){
            $this->throwSocketException('Error during connection establishment.');
        }
    }

    /**
     * Writes to the socket.
     * @param string $data Data to be sent.
     * @return int|void
     */
    public function writeRaw($data)
    {
        return socket_write($this->resource, $data);
    }

    /**
     * Reads from the socket.
     * @param Number $bytes Number of bytes to be read.
     * @return string
     */
    public function readRaw($bytes)
    {
        return socket_read($this->resource, $bytes,PHP_BINARY_READ);
    }

    /**
     * Throws transmitter exception in case of error.
     * @param string $msg Information about error.
     * @throws SocketException Concrete cases depends
     *  on contexts.
     */
    protected function throwSocketException ($msg)
    {
        throw new SocketException(
            $msg.' '.$this->getLastErrorMessage()
        );
    }

    /**
     * Returns last error message on the socket.
     * @return string Error message.
     */
    public function getLastErrorMessage()
    {
        return socket_strerror(socket_last_error($this->resource));
    }
}