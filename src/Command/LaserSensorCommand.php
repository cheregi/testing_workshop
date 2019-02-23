<?php
declare(strict_types=1);

namespace App\Command;

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
     * LaserSensorCommand constructor.
     *
     * @param LaserSensorResolver $sensor
     */
    public function __construct(LaserSensorResolver $sensor)
    {
        $this->sensor = $sensor;
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
        $points = $this->sensor->resolveDetectedPoints(
            floatval($input->getArgument('posX')),
            floatval($input->getArgument('posY')),
            floatval($input->getArgument('angle')),
            floatval($input->getArgument('altitude'))
        );
        $end = microtime(true) - $start;

        if ($output->isVerbose()) {
            $output->writeln(sprintf('<info>%d points resolved</info>', count($points)));
        }

        foreach ($points as $point) {
            if (!$output->isVerbose()) {
                $output->write(
                    sprintf(
                        '%f%4$s%f%4$s%f%4$s',
                        $point['x'],
                        $point['y'],
                        $point['z'],
                        chr(0x1f)
                    )
                );
                continue;
            }
            $output->writeln(
                sprintf(
                    'x:%f y:%f z:%f d:%f a:%f',
                    $point['x'],
                    $point['y'],
                    $point['z'],
                    $point['d'],
                    $point['a']
                )
            );
        }
        $output->writeln($end);
    }

}
