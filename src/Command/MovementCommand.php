<?php
declare(strict_types=1);

namespace App\Command;

use App\Resolver\MovementResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MovementCommand extends Command
{
    /**
     * @var MovementResolver
     */
    private $resolver;

    /**
     * @var float
     */
    private $tickTime;

    /**
     * MovementCommand constructor.
     *
     * @param MovementResolver $resolver
     * @param float            $tickTime
     */
    public function __construct(MovementResolver $resolver, float $tickTime)
    {
        $this->resolver = $resolver;
        $this->tickTime = $tickTime;

        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('app:move')
            ->addOption('ticks', 't', InputOption::VALUE_REQUIRED, 'The tick count to execute', 1)
            ->addOption('wheel-rpm', 'w', InputOption::VALUE_REQUIRED, 'The wheel rpm', 26)
            ->addOption('wheel-rotation-target', null, InputOption::VALUE_REQUIRED, 'The wheel destination rotation', 0)
            ->addOption('wheel-rotation', null, InputOption::VALUE_REQUIRED, 'The wheel rotation', 0)
            ->addOption('position-y', 'y', InputOption::VALUE_REQUIRED, 'The rover Y position', 0)
            ->addOption('position-x', 'x', InputOption::VALUE_REQUIRED, 'The rover X position', 0)
            ->addOption('angel', 'a', InputOption::VALUE_REQUIRED, 'The rover XY angle', 0);
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
        $destRot = floatval($input->getOption('wheel-rotation-target'));
        $rotation = floatval($input->getOption('wheel-rotation'));
        $positionX = floatval($input->getOption('position-x'));
        $positionY = floatval($input->getOption('position-y'));
        $execTime = 0;
        $angel = floatval($input->getOption('angel'));
        $time = 0;
        $ticks = intval($input->getOption('ticks'));
        $wheelRpm = floatval($input->getOption('wheel-rpm'));

        $progress = new ProgressBar($output, $ticks);
        $progress->start();

        for ($i=0;$i < $ticks;$i++, $time += $this->tickTime) {
            $progress->advance();

            $start = microtime(true);
            $info = $this->resolver->resolveMovement($wheelRpm, $rotation, $destRot, $positionX, $positionY, $angel, $this->tickTime);
            $execTime += microtime(true) - $start;

            $rotation = $info->getWheelAngle();
            $positionY = $info->getPositionY();
            $positionX = $info->getPositionX();
            $angel = $info->getAngle();
        }
        $progress->clear();
        $output->writeln(sprintf('Execution time : %fs', $execTime));
        $output->writeln(sprintf('Final wheel rotation : %f', $rotation));
        $output->writeln(sprintf('Final positionX : %f', $positionX));
        $output->writeln(sprintf('Final positionY : %f', $positionY));
        $output->writeln(sprintf('Final angle : %f°', $angel));
        $output->writeln(sprintf('Time : %fs', $time));
        $output->writeln(sprintf('H time : %s', $this->toTimeOutput($time)));
        $output->writeln(sprintf('Ticks : %d', $i));
    }

    private function toTimeOutput($seconds) {
        $t = round($seconds);
        return sprintf('%02d:%02d:%02d', ($t/3600),($t/60%60), $t%60);
    }
}
