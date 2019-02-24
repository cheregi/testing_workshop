<?php
declare(strict_types=1);

namespace App\Command;

use App\Converter\TramConverter;
use App\Resolver\AltimeterResolver;
use App\Resolver\FaceResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AltimeterCommand extends Command
{
    /**
     * @var AltimeterResolver
     */
    private $resolver;

    /**
     * @var FaceResolver
     */
    private $faceResolver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TramConverter
     */
    private $tramConverter;

    /**
     * @param AltimeterResolver $resolver
     * @param FaceResolver      $faceResolver
     * @param LoggerInterface   $logger
     * @param TramConverter     $tramConverter
     */
    public function __construct(
        AltimeterResolver $resolver,
        FaceResolver $faceResolver,
        LoggerInterface $logger,
        TramConverter $tramConverter
    ) {
        $this->resolver = $resolver;
        $this->faceResolver = $faceResolver;
        $this->logger = $logger;
        $this->tramConverter = $tramConverter;

        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('app:sensor:altimeter')
            ->addArgument('positionX', InputArgument::REQUIRED, 'The vehicle position x')
            ->addArgument('positionY', InputArgument::REQUIRED, 'The vehicle position y');
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
        $nearestPoints = $this->faceResolver->getFaceNear(
            floatval($input->getArgument('positionX')),
            floatval($input->getArgument('positionY'))
        );
        $this->logger->debug('Nearest points resolved', [$nearestPoints]);

        $start = microtime(true);
        $altitude = $this->resolver->getAltitude($nearestPoints);
        $end = microtime(true) - $start;

        if ($output->isVerbose()) {
            $output->writeln(sprintf('Altitude : %f meter', $altitude));
            $output->writeln(sprintf('<info>CPU time : %fs</info>', $end));
        }
        $output->writeln($this->tramConverter->convert(TramConverter::DATA_TYPE_ALTIMETER, $altitude));
    }

}
