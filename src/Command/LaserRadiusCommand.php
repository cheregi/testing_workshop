<?php

namespace App\Command;

use App\Amqp\RpcServer;
use App\Converter\TramConverter;
use App\Resolver\AltimeterResolver;
use App\Resolver\FaceResolver;
use App\Resolver\LaserSensorResolver;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LaserRadiusCommand extends Command
{
    /**
     * @var AltimeterResolver
     */
    private $altimeter;

    /**
     * @var LaserSensorResolver
     */
    private $laser;

    /**
     * @var FaceResolver
     */
    private $face;

    /**
     * @var TramConverter
     */
    private $converter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $server;

    /**
     * @param AltimeterResolver   $altimeter
     * @param LaserSensorResolver $laser
     * @param FaceResolver        $face
     * @param TramConverter       $converter
     * @param LoggerInterface     $logger
     * @param RpcServer           $server
     */
    public function __construct(
        AltimeterResolver $altimeter,
        LaserSensorResolver $laser,
        FaceResolver $face,
        TramConverter $converter,
        LoggerInterface $logger,
        RpcServer $server
    ) {
        $this->altimeter = $altimeter;
        $this->laser = $laser;
        $this->face = $face;
        $this->converter = $converter;
        $this->logger = $logger;
        $this->server = $server;

        parent::__construct();
    }

    public function processData(AMQPMessage $request)
    {
        list($newX, $newY) = $this->converter->convert(TramConverter::DATA_TYPE_UPSTREAM_POSITION, $request->getBody());
        $this->logger->debug('New position extracted', compact('newX', 'newY'));

        $altitude = $this->altimeter->getAltitude(
            $this->face->getFaceNear($newX, $newY)
        );
        $this->logger->debug('Altitude resolved', ['altitude' => $altitude]);

        $laserData = $this->laser->resolveDetectedPoints($newX, $newY, 0, $altitude);
        $convert = $this->converter->convert(TramConverter::DATA_TYPE_LASER_SENSOR, $laserData);

        $msg = new AMQPMessage(
            $convert,
            ['correlation_id' => $request->get('correlation_id')]
        );
        $request->delivery_info['channel']->basic_publish(
            $msg,
            '',
            $request->get('reply_to')
        );
        $request->delivery_info['channel']->basic_ack(
            $request->delivery_info['delivery_tag']
        );
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('app:run:laser');
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
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->server->start([$this, 'processData']);
    }
}
