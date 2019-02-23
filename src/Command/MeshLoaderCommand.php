<?php

namespace App\Command;


use App\Document\Coordinates;
use App\Document\MapPoint;
use App\Document\Repository\MapPointRepository;
use App\Parser\MeshParser;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Finder\SplFileInfo;

class MeshLoaderCommand extends Command
{
    const ARG_FILE = 'filePath';

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var MeshParser
     */
    private $meshParser;

    /**
     * @var MapPointRepository
     */
    private $repository;

    /**
     * @var DocumentManager
     */
    private $manager;

    /**
     * MeshLoaderCommand constructor.
     *
     * @param Filesystem         $fileSystem
     * @param MeshParser         $parser
     * @param MapPointRepository $repository
     * @param DocumentManager    $manager
     */
    public function __construct(
        Filesystem $fileSystem,
        MeshParser $parser,
        MapPointRepository $repository,
        DocumentManager $manager
    ) {
        $this->fileSystem = $fileSystem;
        $this->meshParser = $parser;
        $this->repository = $repository;
        $this->manager = $manager;

        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('app:load:mesh')
            ->setDescription('Allow to load the mesh points in the database')
            ->addArgument(static::ARG_FILE, InputArgument::REQUIRED, 'The absolute file path')
            ->addOption('append', 'a', InputOption::VALUE_NONE, 'Does not drop existent points')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Does not execute DB modifications')
            ->addOption('gc-count', null, InputOption::VALUE_REQUIRED, 'point generation count before garbage collection', 100)
            ->addOption('stat', 's', InputOption::VALUE_NONE, 'Get final statistics');
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
        if (!$input->getOption('append') && !$input->getOption('dry-run')) {
            $this->repository->dropAll();
        }

        $file = $this->getFile($input, $output);
        $points = $this->meshParser->parse($file);

        $iteration = 0;
        $gcCount = $input->getOption('gc-count');

        if (!$output->isQuiet() && !$output->isVerbose()) {
            $progress = new ProgressBar($output, $this->meshParser->getPointCount($file));
            $progress->start();
        }
        if ($input->getOption('stat')) {
            $xStat = ['min' => null, 'max' => null];
            $yStat = ['min' => null, 'max' => null];
            $zStat = ['min' => null, 'max' => null];
            $loadedPoints = 0;
        }
        foreach ($points as [$x, $y, $z]) {
            if (isset($xStat) && isset($yStat) && isset($zStat) && isset($loadedPoints)) {
                if ($x < $xStat['min'] || $xStat['min'] === null) {
                    $xStat['min'] = $x;
                }
                if ($x > $xStat['max'] || $xStat['max'] === null) {
                    $xStat['max'] = $x;
                }

                if ($y < $yStat['min'] || $yStat['min'] === null) {
                    $yStat['min'] = $y;
                }
                if ($y > $yStat['max'] || $yStat['max'] === null) {
                    $yStat['max'] = $y;
                }

                if ($z < $zStat['min'] || $zStat['min'] === null) {
                    $zStat['min'] = $z;
                }
                if ($z > $zStat['max'] || $zStat['max'] === null) {
                    $zStat['max'] = $z;
                }
                $loadedPoints++;
            }

            if ($output->isVerbose()) {
                $output->writeln(sprintf('x:%f y:%f z:%f', $x, $y, $z));
            }

            if (!$input->getOption('dry-run')) {
                $mapPoint = new MapPoint();
                $mapPoint->setCoordinates(
                    new Coordinates(
                        floatval($x),
                        floatval($y)
                    )
                )->setElevation(floatval($z));

                $this->manager->persist($mapPoint);
                $this->manager->flush();
                $this->manager->clear();
                unset($mapPoint);

                if (($iteration++) % $gcCount == 0) {
                    gc_collect_cycles();
                }
            }

            if (isset($progress)) {
                $progress->advance();
            }
        }

        if (!$output->isQuiet()) {
            $output->writeln('');
        }

        if (isset($xStat) && isset($yStat) && isset($zStat) && isset($loadedPoints)) {
            $output->writeln(sprintf('<info>%d point loaded</info>', $loadedPoints));
            $output->writeln(sprintf('<info>x range : {%f, %f}</info>', $xStat['min'], $xStat['max']));
            $output->writeln(sprintf('<info>y range : {%f, %f}</info>', $yStat['min'], $yStat['max']));
            $output->writeln(sprintf('<info>z range : {%f, %f}</info>', $zStat['min'], $zStat['max']));
        }
    }

    /**
     * Get file
     *
     * Return the SplFileInfo representation of the file containing the mesh
     *
     * @param InputInterface $input The console input
     * @param OutputInterface $output The console output
     *
     * @return SplFileInfo
     */
    private function getFile(InputInterface $input, OutputInterface $output)
    {
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln('<info>Trying to resolve given file</info>');
        }

        $filePath = $input->getArgument(static::ARG_FILE);
        if (!$this->fileSystem->exists($filePath)) {
            if (!$output->isQuiet()) {
                $output->writeln('<error>Given file is not accessible</error>');
            }
            throw new FileNotFoundException(
                sprintf(
                    'The file %s does not exist',
                    $filePath
                )
            );
        }
        if ($output->isVerbose()) {
            $output->writeln(sprintf('<info>File resolved as %s</info>', $filePath));
            $output->writeln('<info>Check readability for file</info>');
        }

        if (!is_readable($filePath)) {
            if ($output->isVerbose()) {
                $output->writeln('<error>Given file is not readable</error>');
            }
            throw new AccessDeniedException(
                sprintf(
                    'The file %s is not readable',
                    $filePath
                )
            );
        }

        $relativePath = substr($this->fileSystem->makePathRelative(dirname($filePath), getcwd()), 0, -1);
        $fileName = basename($filePath);
        return new SplFileInfo(
            $filePath,
            $relativePath,
            sprintf('%s/%s', $relativePath, $fileName)
        );
    }
}
