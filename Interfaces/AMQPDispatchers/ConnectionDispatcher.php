<?php


namespace MonsterMQ\Interfaces\AMQPDispatchers;


use MonsterMQ\Interfaces\AMQPClass;
use MonsterMQ\Exceptions\PackerException;

interface ConnectionDispatcher extends AMQPClass
{
    public const SUPPORTED_MAJOR_VERSION = 0;
    public const SUPPORTED_MINOR_VERSION = 9;

    public const CLASS_ID = 10;

    public const START_METHOD_ID = 10;
    public const START_OK_METHOD_ID = 11;
    public const SECURE_OK_METHOD_ID = 21;
    public const TUNE_METHOD_ID = 30;
    public const TUNE_OK_METHOD_ID = 31;
    public const OPEN_METHOD_ID = 40;
    public const OPEN_OK_METHOD_ID = 41;
    public const CLOSE_METHOD_ID = 50;
    public const CLOSE_OK_METHOD_ID = 51;

    public const PEER_PROPERTIES = [
        'product' => ['S', 'MonsterMQ'],
        'platform' => ['S', 'PHP'],
        'version' => ['S', '0.1.0'],
        'copyright' => ['S', '']

    ];

    /**
     * Receives Start AMQP method along with its arguments from server. This
     * arguments propose authentication method, locale and also server peer
     * properties.
     */
    public function receive_start(): array;

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
    public function send_start_ok(string $username, string $password, string $locale);

    /**
     * Receive Tune AMQP method along with its arguments. This arguments
     * propose such session parameters as maximum channels number, maximum
     * frame size, and heartbeat timeout.
     */
    public function receive_tune(): array;

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
    public function send_tune_ok(int $channelMax, int $frameMax, int $heartbeat);

    /**
     * This method opens a connection to a
     * virtual host, which is a collection of resources, and acts to separate
     * multiple application domains within a server. The server may apply
     * arbitrary limits per virtual host, such as the number of each type of
     * entity that may be used, per connection and/or in total.
     *
     * @param string $path Virtual host to choose.
     */
    public function send_open(string $path);

    /**
     * This method signals to the client that the connection is ready for use.
     */
    public function receive_open_ok();

    /**
     * Request a connection close.This method indicates that the sender wants
     * to close the connection. This may be due to internal conditions (e.g.
     * a forced shut-down) or due to an error handling a specific method, i.e.
     * an exception. When a close is due to an exception, the sender provides
     * the class and method id of the method which caused the exception. After
     * sending this method, any received methods except Close and Close-OK MUST
     * be discarded. The response to receiving a Close after sending Close must
     * be to send Close-Ok.
     *
     * @return mixed
     */
    //public function send_close();

    /**
     * Confirm a connection close.This method confirms a Connection.Close
     * method and tells the recipient that it is safe to release resources for
     * the connection and close the socket.
     *
     * @return mixed
     */
    //public function send_close_ok();
}