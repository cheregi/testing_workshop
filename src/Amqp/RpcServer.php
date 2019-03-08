<?php

namespace App\Amqp;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class RpcServer
{
    private $connection;

    private $channel;

    public function __construct(array $config)
    {
        $this->connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password']
        );
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare('rpc_queue', false, false, false, false);

        $this->channel->basic_qos(null, 1, null);
    }

    public function start($callback)
    {
        $this->channel->basic_consume('rpc_queue', '', false, false, false, false, $callback);

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    /**
     * PHP 5 introduces a destructor concept similar to that of other object-oriented languages, such as C++.
     * The destructor method will be called as soon as all references to a particular object are removed or
     * when the object is explicitly destroyed or in any order in shutdown sequence.
     *
     * Like constructors, parent destructors will not be called implicitly by the engine.
     * In order to run a parent destructor, one would have to explicitly call parent::__destruct() in the destructor
     * body.
     *
     * Note: Destructors called during the script shutdown have HTTP headers already sent.
     * The working directory in the script shutdown phase can be different with some SAPIs (e.g. Apache).
     *
     * Note: Attempting to throw an exception from a destructor (called in the time of script termination) causes a
     * fatal error.
     *
     * @return void
     * @link https://php.net/manual/en/language.oop5.decon.php
     */
    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }

}
