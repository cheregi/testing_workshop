<?php
declare(strict_types=1);

namespace App\Command;

use App\Amqp\Consumer;
use App\Amqp\Producer;
use App\Amqp\Thread\Task;
use App\Converter\TramConverter;
use App\Resolver\AltimeterResolver;
use App\Resolver\FaceResolver;
use App\Resolver\GyroscopicResolver;
use App\Resolver\LaserSensorResolver;
use App\Resolver\MovementResolver;
use App\Resolver\RoverInformation\DataContainer;
use App\Resolver\RoverInformation\RoverInformation;
use Doctrine\ODM\MongoDB\MongoDBException;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EmulationCommand extends Command
{
    /**
     * @var float
     */
    private $timePerTick;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var float
     */
    private $start;

    /**
     * @var FaceResolver
     */
    private $faceResolver;

    /**
     * @var RoverInformation
     */
    private $roverInformation;

    /**
     * @var AltimeterResolver
     */
    private $altimeterResolver;

    /**
     * @var MovementResolver
     */
    private $movementResolver;

    /**
     * @var float
     */
    private $tickTime;

    /**
     * @var GyroscopicResolver
     */
    private $gyroscopicResolver;

    /**
     * @var LaserSensorResolver
     */
    private $laserResolver;

    /**
     * @var DataContainer
     */
    private $dataContainer;

    /**
     * @var float
     */
    private $wheelAngleDestination = 0.0;

    /**
     * @var array
     */
    private $dataTickConfig;

    /**
     * @var TramConverter
     */
    private $converter;

    /**
     * @var Producer
     */
    private $amqpProcuder;

    /**
     * @var Consumer
     */
    private $amqpConsumer;

    /**
     * @param int                 $tickPerSecond
     * @param float               $tickTime
     * @param array               $dataTickConfig
     * @param LoggerInterface     $logger
     * @param FaceResolver        $faceResolver
     * @param AltimeterResolver   $altimeterResolver
     * @param MovementResolver    $movementResolver
     * @param GyroscopicResolver  $gyroscopicResolver
     * @param LaserSensorResolver $laserResolver
     * @param TramConverter       $converter
     * @param Producer            $producer
     * @param Consumer            $consumer
     */
    public function __construct(
        int $tickPerSecond,
        float $tickTime,
        array $dataTickConfig,
        LoggerInterface $logger,
        FaceResolver $faceResolver,
        AltimeterResolver $altimeterResolver,
        MovementResolver $movementResolver,
        GyroscopicResolver $gyroscopicResolver,
        LaserSensorResolver $laserResolver,
        TramConverter $converter,
        Producer $producer,
        Consumer $consumer
    ) {
        $this->timePerTick = 1000 / $tickPerSecond;
        $this->tickTime = $tickTime;

        if ($logger instanceof Logger) {
            $this->logger = $logger->withName('EMU');
        } else {
            $this->logger = $logger;
        }

        $this->faceResolver = $faceResolver;
        $this->altimeterResolver = $altimeterResolver;
        $this->movementResolver = $movementResolver;
        $this->gyroscopicResolver = $gyroscopicResolver;
        $this->laserResolver = $laserResolver;
        $this->dataContainer = new DataContainer();
        $this->dataTickConfig = $dataTickConfig;
        $this->converter = $converter;
        $this->amqpProcuder = $producer;
        $this->amqpConsumer = $consumer;

        $this->roverInformation = new RoverInformation();

        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('app:start')
            ->addOption('benchmark', 'b', InputOption::VALUE_NONE, 'Benchmark the maximum tps')
            ->addOption('benchmark-decrease', null, InputOption::VALUE_REQUIRED, 'Benchmark tpt decrease in milliseconds', 0.01)
            ->addArgument('positionX', InputArgument::OPTIONAL, 'the initial position X', 0)
            ->addArgument('positionY', InputArgument::OPTIONAL, 'the initial position Y', 0)
            ->addArgument('angle', InputArgument::OPTIONAL, 'the rover angle', 0)
            ->addOption('max-tick', 'm', InputOption::VALUE_REQUIRED, 'Max tick execution', -1)
            ->addOption('max-time', 't', InputOption::VALUE_REQUIRED, 'Max execution time', -1);
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
     * @return int
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->initializeRover($input)) {
            return 1;
        }
        if (!$this->initializeData()) {
            return 1;
        }

        $timePerTick = $this->timePerTick;
        $benchmark = false;
        if ($input->getOption('benchmark')) {
            $benchmark = true;
            $this->logger->debug('Benchmark mode enabled');
            $benchmarkDecrease = floatval($input->getOption('benchmark-decrease'));
            $progress = new ProgressBar($output, intval($timePerTick / $benchmarkDecrease));
            $progress->start();
        }
        $this->logger->debug('Time per tick calculated', ['time' => $timePerTick]);

        $maxTick = $input->getOption('max-tick');
        $maxTime = $input->getOption('max-time');
        $this->start = microtime(true);
        $continue = true;
        $tickCounter = 0;

        $currentLoader = 0;
        $loaders = [];
        for ($i = 0;$i < 40;$i++){
            $tmpLoader = '[';
            for ($j = 0;$j < 40;$j++) {
                if ($j === $i) {
                    $tmpLoader .= ' ';
                    continue;
                }
                $tmpLoader .= '-';
            }
            $tmpLoader .= ']';
            $loaders[] = $tmpLoader;
        }
        $lastSecond = 0;

        if (!$benchmark && $maxTick == 0 && $maxTime == 0) {
            $task = $this->amqpConsumer->start($continue);
        } else {
            $task = new Task([], $continue);
        }
        while ($task->getContinue()) {
            $tickStartedTime = microtime(true) * 1000;
            $this->logger->debug('Tick started');

            try {
                $this->executeTick($tickCounter++);
            } catch (\Exception $e) {
                $this->logger->critical('Exception escaped', ['message' => $e->getMessage(), 'exception' => $e]);
            }
            $curTime = microtime(true);

            if ($maxTick > 0 && $tickCounter > $maxTick) {
                $task->setContinue(false);
            }
            if ($maxTime > 0 && ($curTime - $this->start) > $maxTime) {
                $task->setContinue(false);
            }

            $elapsed = ($curTime * 1000) - $tickStartedTime;
            $remain = $timePerTick - $elapsed;
            $this->logger->debug('Remain', ['time' => $remain, 'elapsed' => $elapsed]);

            $microSecondRemain = intval($remain * 1000);
            if ($microSecondRemain > 0 && !$benchmark) {
                usleep($microSecondRemain);
            } else if ($microSecondRemain <= 0) {
                if ($benchmark) {
                    if (isset($progress)) {
                        $progress->clear();
                    }
                    $output->writeln(sprintf('Max tps : %f', (1000 / $timePerTick)));
                    $task->setContinue(false);
                    $benchmark = false;
                } else {
                    $this->logger->notice('Overclocked', ['delta (ms)' => $microSecondRemain / 1000, 'tps' => (1000 / $timePerTick), 'tpt' => $timePerTick]);
                }
            }
            if ($benchmark) {
                if (isset($progress)) {
                    $progress->advance();
                }
                $timePerTick -= floatval($input->getOption('benchmark-decrease'));
            } else if (intval($curTime * 10) !== $lastSecond) {
                if (++$currentLoader > (count($loaders) - 1)) {
                    $currentLoader = 0;
                }
                $output->write(str_repeat(chr(0x08), strlen($loaders[$currentLoader]) + 20) . $loaders[$currentLoader] . sprintf(' %s MB', number_format(memory_get_usage(true)/1048576,2)));
                $lastSecond = intval($curTime * 10);
                gc_collect_cycles();

                if($this->logger instanceof Logger) {
                    $this->logger->reset();
                }
            }
        };
        $this->logger->debug('Executed time', ['time' => microtime(true) - $this->start]);
        if (!$benchmark) {
            $output->write(str_repeat(chr(0x08), strlen($loaders[0]) + 20));
        }
        if (isset($progress)) {
            $progress->clear();
        }
        $output->writeln(sprintf('Executed ticks : %f%s', $tickCounter, str_repeat(' ', strlen($loaders[0]))));
        $output->writeln(sprintf('Execution time : %fs', microtime(true) - $this->start));
        $output->writeln(sprintf('Memory usage : %f MB', memory_get_usage(true)/1048576,2));

        $this->amqpConsumer->close();
        return 0;
    }

    private function initializeData()
    {
        try {
            $this->executeDataUpdate();
        }catch (\Exception $e) {
            $this->logger->critical('Data initialization failure');
            return false;
        }
        return true;
    }

    private function initializeRover(InputInterface $input)
    {
        $positionX = floatval($input->getArgument('positionX'));
        $positionY = floatval($input->getArgument('positionY'));
        try {
            $this->roverInformation->setAngle(floatval($input->getArgument('angle')))
                ->setPositionX($positionX)
                ->setPositionY($positionY)
                ->setElevation(
                    $this->altimeterResolver->getAltitude(
                        $this->faceResolver->getFaceNear(
                            $positionX,
                            $positionY
                        )
                    )
                );
        } catch (MongoDBException $e) {
            $this->logger->critical('Rover initialization failure');
            return false;
        }
        $this->logger->debug(
            'Rover initialized',
            [
                'x' => $this->roverInformation->getPositionX(),
                'y' => $this->roverInformation->getPositionY(),
                'z' => $this->roverInformation->getElevation(),
                'angle' => $this->roverInformation->getAngle()
            ]
        );

        return true;
    }

    /**
     * @param int $tick
     *
     * @throws MongoDBException
     */
    private function executeDataUpdate(int $tick = 0)
    {
        $position = false;
        $altimeter = false;
        $gyroscope = false;
        $laser = false;
        if ($tick == 0 || $tick % $this->dataTickConfig['tick_per_altimeter'] == 0) {
            $altimeter = true;
        }
        if ($tick == 0 || $tick % $this->dataTickConfig['tick_per_gyroscope'] == 0) {
            $gyroscope = true;
        }
        if ($tick == 0 || $tick % $this->dataTickConfig['tick_per_laser'] == 0) {
            $laser = true;
        }
        if ($tick == 0 || $tick % $this->dataTickConfig['tick_per_position'] == 0) {
            $position = true;
        }

        if ($position) {
            $this->amqpProcuder->addMessage(
                json_encode(
                    [
                        'type' => TramConverter::DATA_TYPE_POSITION,
                        'data' => $this->converter->convert(
                            TramConverter::DATA_TYPE_POSITION,
                            $this->roverInformation
                        )
                    ]
                )
            );
        }

        if ($altimeter || $gyroscope) {
            $this->logger->debug('Nearest point resolution');
            $start = microtime(true);
            $this->dataContainer->setNearestPoint(
                $this->faceResolver->getFaceNear(
                    $this->roverInformation->getPositionX(),
                    $this->roverInformation->getPositionY()
                )
            );
            $this->logger->debug('Nearest point resolved', ['time' => microtime(true) - $start]);
        }

        if ($altimeter) {
            $this->logger->debug('Altimeter resolution');
            $start = microtime(true);
            $this->dataContainer->setElevation(
                $this->altimeterResolver->getAltitude(
                    $this->dataContainer->getNearestPoint()
                )
            );
            $this->roverInformation->setElevation(
                $this->dataContainer->getElevation()
            );
            $this->logger->debug('Altimeter resolved', ['time' => microtime(true) - $start]);
            $this->amqpProcuder->addMessage(
                json_encode(
                    [
                        'type' => TramConverter::DATA_TYPE_ALTIMETER,
                        'data' => $this->converter->convert(
                            TramConverter::DATA_TYPE_ALTIMETER,
                            $this->dataContainer->getElevation()
                        )
                    ]
                )
            );
        }

        if ($gyroscope) {
            $this->logger->debug('Gyroscopic resolution');
            $start = microtime(true);
            $this->dataContainer->setGyroscope(
                $this->gyroscopicResolver->getGyroscopicInfo(
                    $this->dataContainer->getNearestPoint(),
                    $this->roverInformation->getAngle()
                )
            );
            $this->logger->debug('Gyroscopic resolved', ['time' => microtime(true) - $start]);
            $this->amqpProcuder->addMessage(
                json_encode(
                    [
                        'type' => TramConverter::DATA_TYPE_GYROSCOPE,
                        'data' => $this->converter->convert(
                            TramConverter::DATA_TYPE_GYROSCOPE,
                            $this->dataContainer->getGyroscope()
                        )
                    ]
                )
            );
        }

        if ($laser) {
            $this->logger->debug('Laser resolution');
            $start = microtime(true);
            $this->dataContainer->setLaserInformation(
                $this->laserResolver->resolveDetectedPoints(
                    $this->roverInformation->getPositionX(),
                    $this->roverInformation->getPositionY(),
                    $this->roverInformation->getAngle(),
                    $this->roverInformation->getElevation()
                )
            );
            $this->logger->debug('Laser resolved', ['time' => microtime(true) - $start]);
            $this->amqpProcuder->addMessage(
                json_encode(
                    [
                        'type' => TramConverter::DATA_TYPE_LASER_SENSOR,
                        'data' => $this->converter->convert(
                            TramConverter::DATA_TYPE_LASER_SENSOR,
                            $this->dataContainer->getLaserInformation()
                        )
                    ]
                )
            );
        }

        $this->amqpProcuder->flush();
    }

    /**
     * @param int $tick
     *
     * @throws MongoDBException
     */
    private function executeTick(int $tick)
    {
        $moveResult = $this->movementResolver->resolveMovement(
            $this->roverInformation->getWheelRpm(),
            $this->roverInformation->getWheelAngle(),
            $this->wheelAngleDestination,
            $this->roverInformation->getPositionX(),
            $this->roverInformation->getPositionY(),
            $this->roverInformation->getAngle(),
            $this->tickTime
        );
        $this->roverInformation->setPositionX($moveResult->getPositionX())
            ->setPositionY($moveResult->getPositionY())
            ->setAngle($moveResult->getAngle())
            ->setWheelAngle($moveResult->getWheelAngle());

        $this->executeDataUpdate($tick);
    }
}
