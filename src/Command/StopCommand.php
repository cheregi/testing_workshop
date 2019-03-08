<?php

namespace App\Command;

use App\Amqp\Thread\Task;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StopCommand extends Command
{
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('app:stop');
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void null or 0 if everything went fine, or an error code
     *
     * @see setCode()
     * @throws \ErrorException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = new AMQPStreamConnection($this->config['host'], $this->config['port'], $this->config['user'], $this->config['password']);
        $channel = $connection->channel();
        $channel->queue_declare($this->config['back-queue'], false, true, false, false, false);

        $channel->basic_publish(
            new AMQPMessage(json_encode(['data' => ['type' => Task::DATA_TYPE_STOP]])),
            '',
            $this->config['back-queue']
        );

        $channel->close();
        $connection->close();
    }
}
