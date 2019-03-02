<?php
declare(strict_types=1);

namespace App\Command;

use App\Converter\TramConverter;
use App\Resolver\LaserSensorResolver;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LaserSensorCommand extends Command
{
    /**
     * @var LaserSensorResolver
     */
    private $sensor;

    /**
     * @var TramConverter
     */
    private $converter;

    /**
     * LaserSensorCommand constructor.
     *
     * @param LaserSensorResolver $sensor
     * @param TramConverter       $converter
     */
    public function __construct(LaserSensorResolver $sensor, TramConverter $converter)
    {
        $this->sensor = $sensor;
        $this->converter = $converter;
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('app:sensor:laser')
            ->addArgument('posX', InputArgument::REQUIRED, 'The sensor position x on the map')
            ->addArgument('posY', InputArgument::REQUIRED, 'The sensor position y on the map')
            ->addArgument('angle', InputArgument::REQUIRED, 'The sensor angular direction')
            ->addArgument('altitude', InputArgument::REQUIRED, 'The sensor altitude');
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
     * @throws MongoDBException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);
        $positionX = floatval($input->getArgument('posX'));
        $positionY = floatval($input->getArgument('posY'));

        $points = $this->sensor->resolveDetectedPoints(
            $positionX,
            $positionY,
            floatval($input->getArgument('angle')),
            floatval($input->getArgument('altitude'))
        );
        $end = microtime(true) - $start;

        if (!$output->isVerbose()) {
            $output->writeln($this->converter->convert(TramConverter::DATA_TYPE_LASER_SENSOR, $points));
            return;
        }
        foreach ($points as $point) {
            $indent = '';
            if ($point['x'] == 0.0 && $point['y'] == 0.0) {
                $indent = chr(0x09);
            }

            $output->writeln(
                sprintf(
                    '%sx:%f y:%f z:%f d:%f a:%f',
                    $indent,
                    $point['x'],
                    $point['y'],
                    $point['z'],
                    $point['d'],
                    $point['a']
                )
            );
        }
        $output->writeln(sprintf('<info>%d points resolved</info>', count($points)));
        $output->writeln(sprintf('<info>Resolving time : %fs</info>', $end));
    }

}
