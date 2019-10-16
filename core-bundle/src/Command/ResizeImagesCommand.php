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
use Symfony\Component\Console\Terminal;
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

        if ($concurrent > 1) {
            return $this->executeConcurrent($concurrent, $input, $output);
        }

        $startTime = microtime(true);

        $maxDuration = 0;
        $lastDuration = 0;

        foreach ($this->storage->listPaths() as $path) {
            $sleep = (1 - $throttle) / $throttle * $lastDuration;

            if ($timeLimit && microtime(true) - $startTime + $maxDuration + $sleep > $timeLimit) {
                $output->writeln("\n".'Time limit of '.$timeLimit.' seconds reached.');

                return 0;
            }

            usleep((int) ($sleep * 1000000));

            $maxDuration = max($maxDuration, $lastDuration = $this->resizeImage($path, $output));
        }

        $output->writeln("\n".'All images resized.');

        return 0;
    }

    private function resizeImage(string $path, OutputInterface $output): float
    {
        $startTime = microtime(true);

        if ($this->filesystem->exists($path)) {
            return 0;
        }

        $output->write(str_pad($path, $this->terminalWidth + \strlen($path) - mb_strlen($path, 'UTF-8') - 13, '.').' ');

        try {
            $image = $this->imageFactory->create($this->targetDir.'/'.$path);
            $resizer = $this->resizer;

            if ($image instanceof DeferredImageInterface) {
                $resizedImage = $resizer->resizeDeferredImage($image, false);

                if (null === $resizedImage) {
                    // Clear the current output line
                    $output->write("\r".str_repeat(' ', $this->terminalWidth)."\r");
                } else {
                    $output->writeln(sprintf('done%7.3Fs', $duration = microtime(true) - $startTime));

                    return $duration;
                }
            } else {
                // Clear the current output line
                $output->write("\r".str_repeat(' ', $this->terminalWidth)."\r");
            }
        } catch (\Throwable $exception) {
            $output->writeln('failed');
            $output->writeln($exception->getMessage());
        }

        return 0;
    }

    private function executeConcurrent(int $count, InputInterface $input, OutputInterface $output): int
    {
        $phpFinder = new PhpExecutableFinder();

        $output->writeln('Starting '.$count.' concurrent processes...');

        if (false === ($phpPath = $phpFinder->find())) {
            throw new \RuntimeException('The php executable could not be found.');
        }

        /** @var Process[] $processes */
        $processes = [];

        /** @var string[] $buffers */
        $buffers = [];

        for ($i = 0; $i < $count; ++$i) {
            $process = new Process(array_merge(
                [$phpPath],
                $_SERVER['argv'],
                ['--concurrent=1', $output->isDecorated() ? '--ansi' : '--no-ansi']
            ));

            $process->setTimeout(null);
            $process->setEnv(['LINES' => getenv('LINES'), 'COLUMNS' => getenv('COLUMNS') - 4]);
            $process->start();

            $processes[] = $process;
            $buffers[] = '';
        }

        do {
            $isRunning = false;

            foreach ($processes as $index => $process) {
                if ($process->isRunning()) {
                    $isRunning = true;
                }

                // Append new output to remaining buffer from previous loop run
                $buffers[$index] .= $process->getIncrementalOutput();

                // Split output into rows
                $rows = explode("\n", $buffers[$index]);

                // Buffer and remove last line of output, as it might be incomplete
                $buffers[$index] = array_pop($rows);

                // Write remaining rows to the output with thread prefix
                $output->write(array_map(
                    static function ($row) use ($index) {
                        return sprintf('%02d: ', $index + 1).preg_replace('/^.*\r/s', '', $row)."\n";
                    },
                    $rows
                ));
            }

            usleep(15000);
        } while ($isRunning);

        $output->write($buffers);

        $output->write(array_map(
            static function (Process $process): string {
                return $process->getErrorOutput();
            },
            $processes
        ));

        return max(...array_map(
            static function (Process $process): int {
                return (int) $process->getExitCode();
            },
            $processes
        ));
    }
}
