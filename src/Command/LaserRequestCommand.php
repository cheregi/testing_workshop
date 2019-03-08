<?php

namespace App\Command;

use App\Amqp\RpcClient;
use App\Converter\TramConverter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LaserRequestCommand extends Command
{
    /**
     * @var RpcClient
     */
    private $client;

    /**
     * @var TramConverter
     */
    private $converter;

    /**
     * @param RpcClient     $client
     * @param TramConverter $converter
     */
    public function __construct(RpcClient $client, TramConverter $converter)
    {
        $this->client = $client;
        $this->converter = $converter;

        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('app:send:laser_request')
            ->addArgument('positionX', InputArgument::REQUIRED, 'The rover X position')
            ->addArgument('positionY', InputArgument::REQUIRED, 'The rover Y position');
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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(
            $this->client->call(
                $this->converter->convert(
                    TramConverter::DATA_TYPE_REQUEST_POSITION,
                    [
                        floatval($input->getArgument('positionX')),
                        floatval($input->getArgument('positionY'))
                    ]
                )
            )
        );
    }

}
