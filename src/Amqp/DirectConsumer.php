<?php

namespace App\Amqp;

use PhpAmqpLib\Message\AMQPMessage;

class DirectConsumer
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var StoppableChannel
     */
    protected $channel;

    /**
     * @var string
     */
    protected $lastMessage;

    /**
     * DirectConsumer constructor.
     *
     * @param array $config
     *
     * @throws \Exception
     */
    public function __construct(array $config)
    {
        $this->connection = new Connection($config['host'], $config['port'], $config['user'], $config['password']);
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($config['back-queue'], false, true, false, false, false);

        $this->channel->basic_consume($config['back-queue'], '', false, true, false, false, function(AMQPMessage $message) {
            $this->lastMessage = $message->getBody();
        });
    }

    public function getMessage($timeout = 0)
    {
        try {
            $this->channel->wait(null, false, $timeout);
        } catch (\ErrorException $e) {
            return null;
        }

        return $this->lastMessage;
    }
}
