<?php

namespace App\Amqp;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;

class ClientProducer extends Producer
{
    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password']
        );

        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($config['back-queue'], false, true, false, false, false);
        $this->queueName = $config['back-queue'];
        $this->logger = $logger;
    }
}
