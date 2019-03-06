<?php

namespace App\Command;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DataReceiverCommand extends Command
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
        $this->setName('app:test:queue');
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
        $channel->queue_declare($this->config['queue'], false, false, false, false);

        $channel->basic_consume($this->config['queue'], '', false, true, false, false, function(AMQPMessage $message) use ($output) {
            $output->writeln($message->getBody());
        });

        while (count($channel->callbacks)) {
            $channel->wait();
        }
    }
}
