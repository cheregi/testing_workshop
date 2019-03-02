<?php
declare(strict_types=1);

namespace App\Command;

use App\Converter\TramConverter;
use App\Resolver\FaceResolver;
use App\Resolver\Gyroscope\GyroscopicInfoFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GyroscopeCommand extends Command
{
    /**
     * @var FaceResolver
     */
    private $resolver;

    /**
     * @var GyroscopicInfoFactory
     */
    private $gyroFactory;

    /**
     * @var TramConverter
     */
    private $converter;

    /**
     * PlanCommand constructor.
     *
     * @param FaceResolver          $resolver
     * @param GyroscopicInfoFactory $gyroFactory
     * @param TramConverter         $converter
     */
    public function __construct(FaceResolver $resolver, GyroscopicInfoFactory $gyroFactory, TramConverter $converter)
    {
        $this->resolver = $resolver;
        $this->gyroFactory = $gyroFactory;
        $this->converter = $converter;

        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('app:sensor:gyroscope')
            ->addArgument('positionX', InputArgument::REQUIRED, 'The vehicle position x')
            ->addArgument('positionY', InputArgument::REQUIRED, 'The vehicle position y')
            ->addArgument('angle', InputArgument::REQUIRED, 'The rover angle');
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
     * @return int null or 0 if everything went fine, or an error code
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $points = $this->resolver->getFaceNear(
            floatval($input->getArgument('positionX')),
            floatval($input->getArgument('positionY'))
        );

        $info = $this->gyroFactory->getGyroscopicInfo($points, floatval($input->getArgument('angle')));

        if (!$output->isVerbose()) {
            $output->writeln($this->converter->convert(TramConverter::DATA_TYPE_GYROSCOPE, $info));
            return 0;
        }

        $output->writeln(sprintf('xy angle : %f°', $info->getXyAngle()));
        $output->writeln(sprintf('xz angle : %f°', $info->getXzAngle()));
        $output->writeln(sprintf('yz angle : %f°', $info->getYzAngle()));
    }

}
