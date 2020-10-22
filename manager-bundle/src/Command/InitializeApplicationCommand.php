<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Command;

use Contao\ManagerBundle\Process\ProcessFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

/**
 * @internal
 */
class InitializeApplicationCommand extends Command
{
    protected static $defaultName = 'contao:initialize-application';

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var string
     */
    private $webDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    public function __construct(string $projectDir, string $webDir, Filesystem $filesystem = null, ProcessFactory $processFactory = null)
    {
        $this->projectDir = $projectDir;
        $this->webDir = $webDir;
        $this->filesystem = $filesystem ?? new Filesystem();
        $this->processFactory = $processFactory ?? new ProcessFactory();

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHidden(true)
            ->setDescription('Executes all tasks to initialize a Contao Managed Edition. Add this command to your composer "post-install-cmd" and "post-update-cmd" scripts.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->purgeProdCache();

        $commands = [
            ['contao:install-web-dir', '--env=prod'],
            ['cache:clear', '--no-warmup', '--env=prod'],
            ['cache:clear', '--no-warmup', '--env=dev'],
            ['cache:warmup', '--env=prod'],
            ['assets:install', $this->webDir, '--symlink', '--relative', '--env=prod'],
            ['contao:install', $this->webDir, '--env=prod'],
            ['contao:symlinks', $this->webDir, '--env=prod'],
        ];

        $commandFlags = array_filter([
            $output->isDecorated() ? '--ansi' : '--no-ansi',
            $this->getVerbosityFlag($output),
        ]);

        foreach ($commands as $command) {
            $this->executeCommand(array_merge($command, $commandFlags), $output);
        }

        return 0;
    }

    /**
     * Removes the prod cache directory.
     */
    private function purgeProdCache(): void
    {
        $cacheDir = Path::join($this->projectDir, 'var/cache/prod');

        try {
            if (!$this->filesystem->exists($cacheDir)) {
                return;
            }

            $this->filesystem->remove($cacheDir);
        } catch (\Exception $e) {
            // ignore
        }
    }

    /**
     * Executes a console command in its own process and streams the output.
     */
    private function executeCommand(array $command, OutputInterface $output): void
    {
        $process = $this->processFactory->create(
            array_merge(
                [Path::join($this->projectDir, 'vendor/bin', 'contao-console')],
                $command
            )
        );

        // Increase the timeout according to terminal42/background-process (see #54)
        $process->setTimeout(500);

        $process->run(
            static function (string $type, string $buffer) use ($output): void {
                $output->write($buffer);
            }
        );

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('An error occurred while executing the "%s" command: %s', implode(' ', $command), $process->getErrorOutput()));
        }
    }

    private function getVerbosityFlag(OutputInterface $output): string
    {
        switch ($output->getVerbosity()) {
            case OutputInterface::VERBOSITY_DEBUG:
                return '-vvv';

            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                return '-vv';

            case OutputInterface::VERBOSITY_VERBOSE:
                return '-v';

            default:
                return '';
        }
    }
}
