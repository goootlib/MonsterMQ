<?php

namespace MonsterMQ\Interfaces\AMQPDispatchers;

use MonsterMQ\Exceptions\ConnectionException;
use MonsterMQ\Exceptions\PackerException;
use MonsterMQ\Exceptions\ProtocolException;
use MonsterMQ\Exceptions\SessionException;

interface ConnectionDispatcher extends AMQP
{
    public const SUPPORTED_MAJOR_VERSION = 0;
    public const SUPPORTED_MINOR_VERSION = 9;

    public const PEER_PROPERTIES = [
        'product' => ['S', 'MonsterMQ'],
        'platform' => ['S', 'PHP'],
        'version' => ['S', '1.0.0'],
        'copyright' => ['S', 'Copyright (C) 2020 Gleb Zhukov'],
        'information' => ['S','Licensed under the MIT license']

    ];

    /**
     * Receives Start AMQP method along with its arguments from server. This
     * arguments propose authentication method, locale and also server peer
     * properties.
     */
    public function receiveStart(): array;

    /**
     * Select security mechanism and locale. This method also selects a SASL
     * security mechanism and passes credentials.
     *
     * @param string $username          Account name.
     * @param string $password          Password for given account name.
     * @param string $locale            Locale which will be used during session.
     *
     * @throws PackerException In case of unsupported field table type encounter.
     */
    public function sendStartOk(string $username, string $password, string $locale);

    /**
     * Receive Tune AMQP method along with its arguments. This arguments
     * propose such session parameters as maximum channels number, maximum
     * frame size, and heartbeat timeout.
     */
    public function receiveTune(): array;

    /**
     * This method sends the client's
     * connection tuning parameters to the server. Certain fields are
     * negotiated, others provide capability information.
     *
     * @param int $channelMax Maximum number of channels to negotiate.
     * @param int $frameMax   Maximum size of frame to negotiate.
     * @param int $heartbeat  This argument represents time within each
     *                        heartbeat frame must be sent in oder to keep
     *                        connection with server alive, if there was no
     *                        other sendings to the server. If there was no
     *                        sendings to or from server peer should close the
     *                        connection.
     */
    public function sendTuneOk(int $channelMax, int $frameMax, int $heartbeat);

    /**
     * This method opens a connection to a
     * virtual host, which is a collection of resources, and acts to separate
     * multiple application domains within a server. The server may apply
     * arbitrary limits per virtual host, such as the number of each type of
     * entity that may be used, per connection and/or in total.
     *
     * @param string $path Virtual host to choose.
     */
    public function sendOpen(string $path);

    /**
     * This method signals to the client that the connection is ready for use.
     */
    public function receiveOpenOk();

    /**
     * Initiates connection closure. This may be due to internal conditions
     * (e.g. a forced shut-down) or due to an error handling a specific method,
     * i.e. an exception. When a close is due to an exception, the sender
     * provides the class and method id of the method which caused the
     * exception.
     *
     * @param int $replyCode    Reply code.
     * @param string $replyText Reply text.
     * @param int $classId      When the close is provoked by a method
     *                          exception, this is the class of the method.
     * @param int $methodId     When the close is provoked by a method
     *                          exception, this is the ID of the method.
     */
    public function sendClose(
        int $replyCode = 0,
        string $replyText = '',
        int $classId = 0,
        int $methodId = 0
    );

    /**
     * Waits for incoming close-ok AMQP method in oder to close the connection.
     * Client may close the connection if there was no incoming close-ok method
     * during the close-ok timout, which may be specified by setCloseOkTimout.
     *
     * @param int $timeout How long to wait incoming CloseOk method before
     *                     forcing current session closure.
     *
     * @throws ConnectionException
     * @throws SessionException
     * @throws ProtocolException
     */
    public function receiveCloseOK(int $timeout = 130);
}