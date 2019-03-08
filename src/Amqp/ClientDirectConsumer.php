<?php

namespace App\Amqp;

use PhpAmqpLib\Message\AMQPMessage;

class ClientDirectConsumer extends DirectConsumer
{
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
        $this->channel->queue_declare($config['queue'], false, true, false, false, false);

        $this->channel->basic_consume($config['queue'], '', false, true, false, false, function(AMQPMessage $message) {
            $this->lastMessage = $message->getBody();
        });
    }
}
