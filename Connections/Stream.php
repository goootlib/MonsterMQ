<?php

namespace MonsterMQ\Connections;

use MonsterMQ\Exceptions\NetworkException;
use MonsterMQ\Interfaces\Connections\Stream as StreamInterface;
use MonsterMQ\Interfaces\Support\Logger as LoggerInterface;

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
     * Logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Created stream context.
     *
     * @var resource
     */
    protected $context;

    /**
     * Whether to allow self-signed certificates.
     *
     * @var bool
     */
    protected $allowSelfSigned = false;

    /**
     * Require verification of SSL certificate used.
     *
     * @var bool
     */
    protected $verifyPeer = false;

    /**
     * If true - require verification of peer name.
     *
     * @var bool
     */
    protected $verifyPeerName = false;
	
	/**
     * Peer name to be used for peer name verification. If this value is not
     * set, then the name is guessed based on the hostname used when opening
     * the stream.
     *
     * @var string
     */
	protected $peerName;

    /**
     * Location of Certificate Authority file on local filesystem which should
     * be used with the verifyPeer context option to authenticate the identity
     * of the remote peer.
     *
     * @var string
     */
    protected $CA;

    /**
     * Path to local certificate file on filesystem. It must be a PEM encoded
     * file which contains your certificate and private key.  The private key
     * also may be contained in a separate file specified by Stream::privateKey().
     *
     * @var string
     */
    protected $certificate;

    /**
     * Path to local private key file on filesystem in case of separate files
     * for certificate and private key.
     *
     * @var string
     */
    protected $privateKey;

    /**
     * Passphrase with which your certificate file was encoded.
     *
     * @var string
     */
    protected $password;

    /**
     * Enables abortion if the certificate chain is too deep. Defaults to no verification.
     *
     * @var int
     */
    protected $verifyDepth;

    /**
     * List of ciphers to be used for encrypted connection.
     *
     * @var string
     */
    protected $ciphers;

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
    protected $bindAddress = 0;

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
     * Stream constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Enables usage of tls protocol.
     *
     * @return $this
     */
    public function useTLS()
    {
        $this->protocol = 'tls';

        return $this;
    }

    /**
     * Allow self-signed TLS certificates.
     *
     * @return $this
     */
    public function allowSelfSigned()
    {
        $this->allowSelfSigned = true;

        return $this;
    }

    /**
     * Enables verification of SSL certificate used.
     *
     * @return $this
     */
    public function verifyPeer()
    {
        $this->verifyPeer = true;

        return $this;
    }

    /**
     * Enables verification of peer name.
     *
     * @return $this
     */
    public function verifyPeerName()
    {
        $this->verifyPeerName = true;

        return $this;
    }
	
	/**
	 * Sets peer name to be used for peer name verification.
	 *
	 * @param string $name Peer name.
	 *
	 * @return $this
	 */
	public function peerName(string $name)
	{
		$this->peerName = $name;
		
		return $this;
	}

    /**
     * Location of Certificate Authority file on local filesystem which should
     * be used with the Stream::verifyPeer() to authenticate the identity
     * of the remote peer.
     *
     * @param string $certificateAuthorityFile CA file path.
     *
     * @return $this
     */
    public function CA(string $certificateAuthorityFile)
    {
        $this->CA = $certificateAuthorityFile;

        return $this;
    }

    /**
     * Sets path to local certificate file on filesystem. It must be a PEM encoded
     * file which contains your certificate and private key. The private key
     * also may be contained in a separate file specified by Stream::privateKey().
     *
     * @param string $certificateFile Path to certificate file.
     *
     * @return $this
     */
    public function certificate(string $certificateFile)
    {
        $this->certificate = $certificateFile;

        return $this;
    }

    /**
     * Sets path to local private key file on filesystem in case of separate files
     * for certificate and private key.
     *
     * @param string $privateKeyFile Path to private key file.
     *
     * @return $this
     */
    public function privateKey(string $privateKeyFile)
    {
        $this->privateKey = $privateKeyFile;

        return $this;
    }

    /**
     * Sets passphrase with which your certificate file was encoded.
     *
     * @param string $password
     *
     * @return $this
     */
    public function password(string $password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Enables connection abortion if the certificate chain is too deep.
     *
     * @param int $depth Depth of certificate chain.
     *
     * @return $this
     */
    public function verifyDepth(int $depth)
    {
        $this->verifyDepth = $depth;

        return $this;
    }

    /**
     * Sets list of ciphers to be used for connection. List of all system
     * supported ciphers in format that this method accept may be obtained
     * by 'openssl ciphers' cli command.
     *
     * @param string $ciphers List of ciphers.
     *
     * @return $this
     */
    public function ciphers(string $ciphers)
    {
        $this->ciphers = $ciphers;

        return $this;
    }

    /**
     * Which ip address and port will be used for binding.
     *
     * @param int    $port    Port to be used for binding.
     * @param string $address IP address to be used for binding.
     *
     * @return $this For chaining purposes.
     */
    public function bindTo(int $port = 0, string $address = null)
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
        if (PHP_VERSION_ID >= 70100) {
            $this->tcpNodelay = true;
        }

        return $this;
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
     * Disables blocking mode. In non-blocking mode an fgets() call will always
     * return right away while in blocking mode it will wait for data to become
     * available on the stream.
     */
    public function disableBlockingMode()
    {
        if ($this->isConnected()) {
            stream_set_blocking($this->streamResource, false);
        }
    }

    /**
     * Enables blocking mode.
     */
    public function enableBlockingMode()
    {
        if ($this->isConnected()) {
            stream_set_blocking($this->streamResource, true);
        }
    }

    /**
     * Sets reading/writing timeout after which reading from or writing to
     * socket fails.
     *
     * @param int|float $seconds      In case of int type of the first argument,
     *                                the second argument also must be set. In
     *                                case of float type of first argument
     *                                fractional part of float number will be
     *                                treated as microseconds and will be used
     *                                instead of second argument.
     * @param int       $microseconds Defines microseconds part of reading
     *                                timeout.
     *
     * @return $this For chaining purposes.
     */
    public function setTimeout($seconds, int $microseconds = 0)
    {
        if (!is_int($seconds) && !is_float($seconds)) {
            throw new \InvalidArgumentException(
                'Error while setting reading/writing timeout. Provided 
                "seconds" argument is not an integer or a float.'
            );
        } elseif (is_int($seconds) && !is_int($microseconds)) {
            throw new \InvalidArgumentException(
                "Error while setting reading/writing timeout. If first argument 
                is integer(which represents seconds), second argument (which represents 
                microseconds) must be integer too."
            );
        }

        if (is_float($seconds)) {
            //Fractional part of float multiplied by number of microseconds in one second.
            $microseconds = fmod($seconds,1) * 1000000;
            $seconds = floor($seconds);
        }
        $this->timeout = [$seconds, $microseconds];

        return $this;
    }

    /**
     * Recreates context resource.
     */
    protected function refreshContext()
    {
        if ($this->protocol == 'tcp') {
            $socketContextOptions = ['bindto' => "{$this->bindAddress}:{$this->bindPort}"];

            if ($this->tcpNodelay) {
                $socketContextOptions = array_merge($socketContextOptions, ['tcp_nodelay' => true]);
            }

            $address = $this->bindAddress ? "to address {$this->bindAddress} and " : "";
            $port = $this->bindPort ? "to port {$this->bindPort}" : "";
            if (!empty($address . $port)) {
                $this->logger->write("Binding socket " . $address . $port);
            }

            $this->context = stream_context_create([
                'socket' => $socketContextOptions
            ]);
        } else {
            $contextOptions = $this->getTLSContextOptions();

            if ($contextOptions['allow_self_signed']) {
                $message = 'with self-signed certificates allowed';
            } else {
                $message = $contextOptions['verify_peer'] ? 'peer verification' : '';
                $message .= $contextOptions['verify_peer_name'] ? 'and peer name verification' : '';
                $message = !empty($message) ? 'with '.$message.' enabled' : '';
            }

            $this->logger->write('Setting up TLS connection context '.$message);

            $this->context = stream_context_create([
                'tls' => $contextOptions
            ]);
        }

        $this->contextAvailable = true;
    }

    /**
     * Returns TLS context options which would be used for TLS context creation.
     *
     * @return array TLS context options.
     */
    protected function getTLSContextOptions()
    {
        $TLSContextOptions = [
            'verify_peer' => $this->verifyPeer,
            'verify_peer_name' => $this->verifyPeerName,
            'allow_self_signed' => $this->allowSelfSigned
        ];

        if (isset($this->peerName)) {
            $TLSContextOptions['peer_name'] = $this->peerName;
        }

        if (isset($this->CA)) {
            $TLSContextOptions['cafile'] = $this->CA;
        }

        if (isset($this->certificate)) {
            $TLSContextOptions['local_cert'] = $this->certificate;
        }

        if (isset($this->privateKey)) {
            $TLSContextOptions['local_pk'] = $this->privateKey;
        }

        if (isset($this->password)) {
            $TLSContextOptions['passphrase'] = $this->password;
        }

        if (isset($this->verifyDepth)) {
            $TLSContextOptions['verify_depth'] = $this->verifyDepth;
        }

        if (isset($this->ciphers)) {
            $TLSContextOptions['ciphers'] = $this->ciphers;
        }

        return $TLSContextOptions;
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

        $this->logger->write("Applying socket writing/reading timeout to {$seconds} seconds and {$microseconds} microseconds");

        return stream_set_timeout($this->streamResource, $seconds, $microseconds);
    }

    /**
     * Keepalive available only if "sockets" php extension available.
     *
     * @return bool Whether keepalive available or not.
     */
    public function keepaliveAvailable(): bool
    {
        if (
            defined('SOL_SOCKET')
            && defined('SO_KEEPALIVE')
            && function_exists('socket_import_stream')
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Enables keepalive connection option.
     */
    protected function applyKeepalive()
    {
        if ($this->keepaliveEnabled && $this->protocol == 'tcp' && $this->keepaliveAvailable()) {

            $this->logger->write('Enabling keepalive');

            $socket = socket_import_stream($this->streamResource);
            socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
        }
    }

    /**
     * Connects to specified address and port.
     *
     * @param string            $address           IP address which AMQP server
     *                                             listens.
     * @param int               $AMQPport          Port to which AMQP server
     *                                             was bound.
     * @param float             $connectionTimeout Time after which connection
     *                                             attempt fails.
     *
     * @throws NetworkException                    Throws in case of connection
     *                                             could not be established.
     */
    public function connect (string $address = '127.0.0.1', int $AMQPport = 5672, float $connectionTimeout = null)
    {
        $this->refreshContext();
        
        $this->logger->write("Connecting to {$address} on port {$AMQPport} by protocol {$this->protocol}");

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
     * @return bool Whether connection closed or not.
     */
    public function connectionClosed(): bool
    {
        if (!$this->streamResource) {
            return true;
        }
        return false;
    }

    /**
     * Whether connected.
     *
     * @return bool Whether connected.
     */
    public function isConnected(): bool
    {
        if (is_resource($this->streamResource)) {
            return true;
        }
        return false;
    }

    /**
     * Writes to the socket.
     *
     * @param string            $data Data to be sent.
     *
     * @return int|void               Amount of data has been written.
     *
     * @throws NetworkException       In case of closure connection or writing error.
     */
    public function writeRaw(string $data): int
    {
        if($this->connectionClosed()){
            throw new NetworkException('Connection was closed.');
        }

        $dataLength = strlen($data);
        $written = 0;
        while ($written < $dataLength){
            $result = fwrite($this->streamResource, $data, self::WRITE_BUFFER_SIZE);

            if($result === false){
                throw new NetworkException('Error sending data.');
            }

            $written += $result;

            $data = substr($data, $written);
        }

        return $written;
    }

    /**
     * Reads from the socket.
     *
     * @param int $length Number of bytes to be read.
     *
     * @return string Data received from remote peer.
     *
     * @throws NetworkException In case of connection closure or reading error.
     */
    public function readRaw(int $length): ?string
    {
        if($this->connectionClosed()){
            throw new NetworkException('Connection was closed.');
        }

        if ($length == 0) {
            return null;
        }

        $result = fread($this->streamResource, $length);

        return $result;
    }

    /**
     * Closes network connection.
     *
     * Before closing network connections don't
     * forget to send Connection.Close method to the server. Or hand-shake
     * incoming Connection.Close with Connection.CloseOk.
     */
    public function close()
    {
        if (isset($this->streamResource)) {
            $this->logger->write("Closing socket connection");
            $this->context = null;
            stream_socket_shutdown($this->streamResource, STREAM_SHUT_RDWR);
        }

        $this->protocol = 'tcp';
    }
}