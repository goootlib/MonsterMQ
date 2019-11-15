<?php


namespace MonsterMQ\Interfaces\AMQPDispatchers;


use MonsterMQ\Interfaces\AMQPClass;

interface ConnectionDispatcher extends AMQPClass
{
    public const SUPPORTED_MAJOR_VERSION = 0;
    public const SUPPORTED_MINOR_VERSION = 9;

    public const CLASS_ID = 10;

    public const START_METHOD_ID = 10;
    public const START_OK_METHOD_ID = 11;
    public const SECURE_OK_METHOD_ID = 21;
    public const TUNE_OK_METHOD_ID = 31;
    public const OPEN_METHOD_ID = 40;
    public const CLOSE_METHOD_ID = 50;
    public const CLOSE_OK_METHOD_ID = 51;

    /**
     * Receives Start method along with its arguments from server.
     *
     * @return mixed
     */
    public function receive_start();

    /**
     * Select security mechanism and locale. This method selects a SASL
     * security mechanism.
     *
     * @return mixed
     */
    //public function send_start_ok();

    /**
     * Security mechanism challenge. The SASL protocol works by exchanging
     * challenges and responses until both peers have received sufficient
     * information to authenticate each other. This method challenges the
     * client to provide more information.
     *
     * @return mixed
     */
    //public function send_secure_ok();

    /**
     * Negotiate connection tuning parameters.This method sends the client's
     * connection tuning parameters to the server. Certain fields are
     * negotiated, others provide capability information.
     *
     * @return mixed
     */
    //public function send_tune_ok();

    /**
     * Open connection to virtual host. This method opens a connection to a
     * virtual host, which is a collection of resources, and acts to separate
     * multiple application domains within a server. The server may apply
     * arbitrary limits per virtual host, such as the number of each type of
     * entity that may be used, per connection and/or in total.
     *
     * @return mixed
     */
    //public function send_open();

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