<?php

namespace MonsterMQ\Interfaces\Connections;

Interface Stream
{
    /**
     * Enables usage of tls protocol.
     *
     * @return \MonsterMQ\Connections\Stream
     */
    public function useTLS();

    /**
     * Allow self-signed TLS certificates.
     *
     * @return \MonsterMQ\Connections\Stream
     */
    public function allowSelfSigned();

    /**
     * Enables verification of SSL certificate used.
     *
     * @return \MonsterMQ\Connections\Stream
     */
    public function verifyPeer();

    /**
     * Enables verification of peer name.
     *
     * @return \MonsterMQ\Connections\Stream
     */
    public function verifyPeerName();

    /**
     * Sets peer name to be used for peer name verification.
     *
     * @param string $name Peer name.
     *
     * @return \MonsterMQ\Connections\Stream
     */
    public function peerName(string $name);

    /**
     * Location of Certificate Authority file on local filesystem which should
     * be used with the Stream::verifyPeer() to authenticate the identity
     * of the remote peer.
     *
     * @param string $certificateAuthorityFile CA file path.
     *
     * @return \MonsterMQ\Connections\Stream
     */
    public function CA(string $certificateAuthorityFile);

    /**
     * Sets path to local certificate file on filesystem. It must be a PEM encoded
     * file which contains your certificate and private key. The private key
     * also may be contained in a separate file specified by Stream::privateKey().
     *
     * @param string $certificateFile Path to certificate file.
     *
     * @return \MonsterMQ\Connections\Stream
     */
    public function certificate(string $certificateFile);

    /**
     * Sets path to local private key file on filesystem in case of separate files
     * for certificate and private key.
     *
     * @param string $privateKeyFile Path to private key file.
     *
     * @return \MonsterMQ\Connections\Stream
     */
    public function privateKey(string $privateKeyFile);

    /**
     * Sets passphrase with which your certificate file was encoded.
     *
     * @param string $password
     *
     * @return \MonsterMQ\Connections\Stream
     */
    public function password(string $password);

    /**
     * Enables connection abortion if the certificate chain is too deep.
     *
     * @param int $depth Depth of certificate chain.
     *
     * @return \MonsterMQ\Connections\Stream
     */
    public function verifyDepth(int $depth);

    /**
     * Sets comma-separated list of ciphers.
     *
     * @param string $ciphers Comma-separated list of ciphers.
     *
     * @return \MonsterMQ\Connections\Stream
     */
    public function ciphers(string $ciphers);

    /**
     * Whether connected.
     *
     * @return bool Whether connected.
     */
    public function isConnected(): bool;

    /**
     * Whether connection closed or not.
     *
     * @return bool Whether connection closed or not.
     */
    public function connectionClosed(): bool;

    /**
     * Disables keepalive.
     *
     * @return Stream For chaining purposes.
     */
    public function disableKeepalive();

    /**
     * Disables Nagle's algorithm.
     *
     * @return Stream For chaining purposes.
     */
    public function enableNodelay();

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
     * @return Stream For chaining purposes.
     */
    public function setTimeout($seconds, int $microseconds = 0);

    /**
     * Which ip address and port will be used for binding.
     *
     * @param int $port    Port to be used for binding.
     * @param string $address IP address to be used for binding.
     *
     * @return Stream For chaining purposes.
     */
    public function bindTo(int $port, string $address);

    /**
     * Opens the network connection to specified address
     * and port.
     * @param string $address
     * @param int $AMQPport
     * @return mixed
     */
    public function connect (string $address = '127.0.0.1', int $AMQPport = 5672, float $connectionTimeout = null);

    /**
     * This method writes data to previously
     * configured socket connection.
     *
     * @param string $data Data that will be transmitted
     * @return void
     */
    public function writeRaw (string $data): int;

    /**
     * This method reads from the established
     * network connection.
     *
     * @param int $length Number of bytes to be read
     * @return string Data received through connection
     */
    public function readRaw (int $length): ?string;

    /**
     * Closes current network connection.
     * @return mixed
     */
    public function close();
}