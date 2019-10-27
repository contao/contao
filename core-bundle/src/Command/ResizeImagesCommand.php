<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Command;

use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredImageStorageInterface;
use Contao\Image\DeferredResizerInterface;
use Contao\Image\ResizerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Resize deferred images that have not been processed yet.
 */
class ResizeImagesCommand extends Command
{
    /**
     * @var ImageFactoryInterface
     */
    private $imageFactory;

    /**
     * @var ?DeferredResizerInterface
     */
    private $resizer;

    /**
     * @var string
     */
    private $targetDir;

    /**
     * @var DeferredImageStorageInterface
     */
    private $storage;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var int
     */
    private $terminalWidth;

    public function __construct(ImageFactoryInterface $imageFactory, ResizerInterface $resizer, string $targetDir, DeferredImageStorageInterface $storage, Filesystem $filesystem = null)
    {
        $this->imageFactory = $imageFactory;
        $this->resizer = $resizer instanceof DeferredResizerInterface ? $resizer : null;
        $this->targetDir = $targetDir;
        $this->storage = $storage;
        $this->filesystem = $filesystem ?? new Filesystem();
        $this->terminalWidth = (new Terminal())->getWidth();

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('contao:resize-images')
            ->setDefinition([
                new InputOption('time-limit', 'l', InputArgument::OPTIONAL, 'Time limit in seconds', '0'),
                new InputOption('concurrent', 'c', InputArgument::OPTIONAL, 'Run multiple processes concurrently', '1'),
                new InputOption('throttle', 't', InputArgument::OPTIONAL, 'Pause between resizes to limit CPU utilization, 0.1 relates to 10% CPU usage', '1'),
                new InputOption('image', null, InputArgument::OPTIONAL, 'Image name to resize a single image'),
            ])
            ->setDescription('Resizes deferred images that have not been processed yet.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->resizer instanceof DeferredResizerInterface) {
            throw new \RuntimeException('Deferred resizer not available');
        }

        $io = new SymfonyStyle($input, $output);
        $image = $input->getOption('image');

        if (null !== $image) {
            return $this->resizeImage($image, $io);
        }

        $timeLimit = (float) $input->getOption('time-limit');
        $throttle = (float) $input->getOption('throttle');
        $concurrent = (int) $input->getOption('concurrent');

        if ($timeLimit < 0) {
            throw new InvalidArgumentException(sprintf('Time-limit value "%s" is invalid.', $timeLimit));
        }

        if ($throttle < 0.001 || $throttle > 1) {
            throw new InvalidArgumentException(sprintf('Throttle value "%s" is invalid.', $throttle));
        }

        if ($concurrent < 1) {
            throw new InvalidArgumentException(sprintf('Concurrent value "%s" is invalid.', $concurrent));
        }

        return $this->resizeImages($timeLimit, $throttle, $concurrent, $io);
    }

    private function resizeImage(string $path, SymfonyStyle $io): int
    {
        $startTime = microtime(true);

        if ($this->filesystem->exists($path)) {
            return 0;
        }

        $io->write(str_pad($path, $this->terminalWidth + \strlen($path) - mb_strlen($path, 'UTF-8') - 13, '.').' ');

        try {
            $image = $this->imageFactory->create($this->targetDir.'/'.$path);
            $resizer = $this->resizer;

            if ($image instanceof DeferredImageInterface) {
                $resizedImage = $resizer->resizeDeferredImage($image, false);

                if (null === $resizedImage) {
                    // Clear the current output line
                    $io->write("\r".str_repeat(' ', $this->terminalWidth)."\r");
                } else {
                    $io->writeln(sprintf('done%7.3Fs', $duration = microtime(true) - $startTime));

                    return 0;
                }
            } else {
                // Clear the current output line
                $io->write("\r".str_repeat(' ', $this->terminalWidth)."\r");
            }
        } catch (\Throwable $exception) {
            $io->writeln('failed');

            if ($io->isVerbose()) {
                $io->error(FlattenException::createFromThrowable($exception)->getAsString());
            } else {
                $io->writeln($exception->getMessage());
            }

            return 1;
        }

        return 0;
    }

    private function resizeImages(float $timeLimit, float $throttle, int $concurrent, SymfonyStyle $io)
    {
        if ($this->supportsSubProcesses()) {
            $this->executeConcurrent($timeLimit, $throttle, $concurrent, $io);
        }
        else {
            $this->executeInCurrentProcess($timeLimit, $throttle, $io);
        }

        $io->writeln("\n".'All images resized.');

        return 0;
    }

    private function supportsSubProcesses(): bool
    {
        $phpFinder = new PhpExecutableFinder();

        if (false === ($phpPath = $phpFinder->find())) {
            return false;
        }

        $process = new Process(array_merge(
            [$phpPath],
            $_SERVER['argv'],
            ['--help']
        ));

        return 0 === $process->run();
    }

    private function executeInCurrentProcess(float $timeLimit, float $throttle, SymfonyStyle $io): void
    {
        $startTime = microtime(true);

        $maxDuration = 0;
        $lastDuration = 0;

        foreach ($this->storage->listPaths() as $path) {
            $sleep = (1 - $throttle) / $throttle * $lastDuration;

            if ($timeLimit && microtime(true) - $startTime + $maxDuration + $sleep > $timeLimit) {
                $io->writeln("\n".'Time limit of '.$timeLimit.' seconds reached.');

                return;
            }

            usleep((int) ($sleep * 1000000));

            $resizeStart = microtime(true);

            $this->resizeImage($path, $io);

            $lastDuration = microtime(true) - $resizeStart;
            $maxDuration = max($maxDuration, $lastDuration);
        }
    }

    private function executeConcurrent(float $timeLimit, float $throttle, int $concurrent, SymfonyStyle $io): int
    {
        $phpFinder = new PhpExecutableFinder();
        $phpPath = $phpFinder->find();

        /** @var Process[] $processes */
        $processes = array_fill(0, $concurrent, null);

        $paths = array_fill(0, $concurrent, '');

        $startTimes = array_fill(0, $concurrent, 0);

        foreach ($this->storage->listPaths() as $path) {
            while (true) {
                foreach ($processes as $index => $process) {

                    if (null !== $process) {
                        if ($process->isRunning()) {
                            continue;
                        }

                        $this->finishSubProcess($process, $paths[$index], $startTimes[$index], $io);
                    }

                    $process = new Process(array_merge(
                        [$phpPath],
                        $_SERVER['argv'],
                        ['--image='.$path, $io->isDecorated() ? '--ansi' : '--no-ansi']
                    ));

                    $process->setTimeout(null);
                    $process->setEnv(['LINES' => getenv('LINES'), 'COLUMNS' => getenv('COLUMNS')]);
                    $process->start();

                    $processes[$index] = $process;
                    $startTimes[$index] = microtime(true);
                    $paths[$index] = $path;

                    continue 3;
                }

                usleep(15000);
            }
        }

        foreach ($processes as $index => $process) {
            if (null === $process) {
                continue;
            }
            $this->finishSubProcess($process, $paths[$index], $startTimes[$index], $io);
        }

        return 0;
    }

    private function finishSubProcess(Process $process, string $path, float $startTime, SymfonyStyle $io)
    {
        $process->wait();
        $io->write(str_pad($path, $this->terminalWidth + \strlen($path) - mb_strlen($path, 'UTF-8') - 13, '.').' ');
        if ($process->isSuccessful()) {
            $io->writeln(sprintf('done%7.3Fs', microtime(true) - $startTime));
        } else {
            $io->writeln('failed');
        }
    }
}
