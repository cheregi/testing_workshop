<?php
declare(strict_types=1);

namespace App\Command;

use App\Document\MapPoint;
use App\Resolver\FaceResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NearestPointsCommand extends Command
{
    /**
     * @var FaceResolver
     */
    private $resolver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param FaceResolver    $resolver
     * @param LoggerInterface $logger
     */
    public function __construct(FaceResolver $resolver, LoggerInterface $logger)
    {
        $this->resolver = $resolver;
        $this->logger = $logger;
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('app:points:nearest')
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
        $start = microtime(true);
        $points = $this->resolver->getFaceNear(
            floatval($input->getArgument('positionX')),
            floatval($input->getArgument('positionY'))
        );
        $end = microtime(true) - $start;
        $this->logger->debug('Points resolved', ['points' => $points]);

        if ($points->getTopRightPoint()) {
            $this->displayPoint('Top right', $points->getTopRightPoint(), $output);
        } else {
            $output->writeln('<error>No top right point</error>');
        }
        if ($points->getTopLeftPoint()) {
            $this->displayPoint('Top left', $points->getTopLeftPoint(), $output);
        } else {
            $output->writeln('<error>No top left point</error>');
        }
        if ($points->getBottomLeftPoint()) {
            $this->displayPoint('Bottom left', $points->getBottomLeftPoint(), $output);
        } else {
            $output->writeln('<error>No bottom left point</error>');
        }
        if ($points->getBottomRightPoint()) {
            $this->displayPoint('Bottom right', $points->getBottomRightPoint(), $output);
        } else {
            $output->writeln('<error>No bottom right point</error>');
        }
        if ($points->getExactPoint()) {
            $this->displayPoint('Exact', $points->getExactPoint(), $output);
        } else {
            $output->writeln('<comment>No exact point</comment>');
        }
        $output->writeln(sprintf('<info>CPU time : %fs</info>', $end));
    }

    private function displayPoint(string $location, MapPoint $point, OutputInterface $output)
    {
        $output->writeln(
            sprintf(
                $location . ' point : {%f, %f, %f}',
                $point->getCoordinates()->getPositionX(),
                $point->getCoordinates()->getPositionY(),
                $point->getElevation()
            )
        );
    }
}
