<?php

namespace App\Amqp;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class Producer
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var AMQPStreamConnection
     */
    protected $connection;

    /**
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    protected $channel;

    /**
     * @var string
     */
    protected $queueName;

    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password']
        );

        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($config['queue'], false, true, false, false, false);
        $this->queueName = $config['queue'];
        $this->logger = $logger;
    }

    public function addMessage(string $message)
    {
        try {
            $this->logger->debug('Adding information to queue');

            $amqpMessage = new AMQPMessage($message);
            $this->channel->batch_basic_publish(
                $amqpMessage,
                '',
                $this->queueName
            );
            $this->logger->debug('Done');
        } catch (AMQPRuntimeException $exception) {
            $this->logger->error(
                'Sending message failed',
                ['message' => $exception->getMessage(), 'exception' => $exception]
            );
        }
    }

    public function flush()
    {
        $start = microtime(true);
        $this->logger->debug('Sending information to queue');
        if ($this->channel->publish_batch() === false) {
            $this->logger->debug('Empty set');
            return;
        }
        $this->logger->debug('Information sent', ['time' => microtime(true) - $start]);
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
