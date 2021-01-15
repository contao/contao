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
use Symfony\Component\Process\PhpExecutableFinder;
use Webmozart\PathUtil\Path;

/**
 * @internal
 */
class ContaoSetupCommand extends Command
{
    protected static $defaultName = 'contao:setup';

    /**
     * @var string
     */
    private $webDir;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @var string|false
     */
    private $phpPath;

    /**
     * @var string
     */
    private $consolePath;

    public function __construct(string $projectDir, string $webDir, ProcessFactory $processFactory = null)
    {
        $this->webDir = Path::makeRelative($webDir, $projectDir);
        $this->phpPath = (new PhpExecutableFinder())->find();
        $this->consolePath = Path::canonicalize(__DIR__.'/../../bin/contao-console');

        $this->processFactory = $processFactory ?? new ProcessFactory();

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHidden(true)
            ->setDescription('Sets up a Contao Managed Edition. This command will be run when executing the "contao-setup" binary.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (false === $this->phpPath) {
            throw new \RuntimeException('The php executable could not be found.');
        }

        $php = [$this->phpPath];

        if (OutputInterface::VERBOSITY_DEBUG === $output->getVerbosity()) {
            $php[] = '-ddisplay_errors=-1';
            $php[] = '-ddisplay_startup_errors=-1';
        }

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
            $this->executeCommand(array_merge($php, [$this->consolePath], $command, $commandFlags), $output);
        }

        $output->writeln('<info>Done! Please open the Contao install tool or run contao:migrate on the command line to make sure the database is up-to-date.</info>');

        return 0;
    }

    /**
     * Executes a console command in its own process and streams the output.
     */
    private function executeCommand(array $command, OutputInterface $output): void
    {
        $process = $this->processFactory->create($command);

        // Increase the timeout according to contao/manager-bundle (see #54)
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
