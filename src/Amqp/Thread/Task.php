<?php

namespace App\Amqp\Thread;

use App\Amqp\Connection;
use PhpAmqpLib\Message\AMQPMessage;

class Task extends \Threaded
{
    const DATA_TYPE_STOP = 0;

    private $config;

    private $continue;

    /**
     * Task constructor.
     *
     * @param $config
     * @param $continue
     */
    public function __construct($config, &$continue)
    {
        $this->config = $config;
        $this->continue = $continue;
    }

    /**
     * (PECL pthreads &gt;= 2.0.0)<br/>
     * The programmer should always implement the run method for objects
     * that are intended for execution.
     *
     * @link https://secure.php.net/manual/en/threaded.run.php
     * @return void
     * @throws \ErrorException
     * @throws \Exception
     */
    public function run()
    {
        $connection = new Connection($this->config['host'], $this->config['port'], $this->config['user'], $this->config['password']);
        $channel = $connection->channel();
        $channel->queue_declare($this->config['back-queue'], false, true, false, false, false);

        $channel->basic_consume($this->config['back-queue'], '', false, true, false, false, function(AMQPMessage $message) {
            $data = json_decode($message->getBody(), true)['data'];
            switch ($data['type']) {
                case static::DATA_TYPE_STOP:
                    $this->setContinue(false);
                    $message->delivery_info['channel']->stopWait();
                    break;
            }
        });

        try {
            while (count($channel->callbacks) && !$channel->isStopped()) {
                $channel->wait(null, true, 0);
            }

            $channel->close();
            $connection->close();
        } catch (\ErrorException $e) {
            $channel->close();
            $connection->close();
            return;
        }
    }

    /**
     * @return mixed
     */
    public function getContinue()
    {
        return $this->continue;
    }

    /**
     * @param mixed $continue
     */
    public function setContinue($continue): void
    {
        $this->continue = $continue;
    }
}
