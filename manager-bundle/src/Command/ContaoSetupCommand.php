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

use Contao\ManagerBundle\Dotenv\DotenvDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
class ContaoSetupCommand extends Command
{
    protected static $defaultName = 'contao:setup';
    protected static $defaultDescription = 'Sets up a Contao Managed Edition. This command will be run when executing the "contao-setup" binary.';

    private string $projectDir;
    private string $webDir;
    private string $kernelSecret;
    private string $consolePath;

    /**
     * @var \Closure(array<string>):Process
     */
    private \Closure $createProcessHandler;

    /**
     * @var string|false
     */
    private $phpPath;

    /**
     * @param (\Closure(array<string>):Process)|null $createProcessHandler
     */
    public function __construct(string $projectDir, string $webDir, ?string $kernelSecret, ?\Closure $createProcessHandler = null)
    {
        $this->projectDir = $projectDir;
        $this->webDir = Path::makeRelative($webDir, $projectDir);
        $this->kernelSecret = $kernelSecret;
        $this->phpPath = (new PhpExecutableFinder())->find();
        $this->consolePath = Path::canonicalize(__DIR__.'/../../bin/contao-console');

        $this->createProcessHandler = $createProcessHandler ?? static fn (array $command) => new Process($command);

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHidden(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Auto-generate a kernel secret if none was set
        if (empty($this->kernelSecret) || 'ThisTokenIsNotSoSecretChangeIt' === $this->kernelSecret) {
            $filesystem = new Filesystem();

            $dotenv = new DotenvDumper(Path::join($this->projectDir, '.env.local'), $filesystem);
            $dotenv->setParameter('APP_SECRET', bin2hex(random_bytes(32)));
            $dotenv->dump();

            $io->info('An APP_SECRET was generated and written to your .env.local file.');

            if (!$filesystem->exists($envPath = Path::join($this->projectDir, '.env'))) {
                $filesystem->dumpFile($envPath, "#DATABASE_URL='mysql://username:password@localhost/database_name'\n#MAILER_DSN=\n#COOKIE_ALLOW_LIST=PHPSESSID,csrf_https-contao_csrf_token,csrf_contao_csrf_token,trusted_device,REMEMBERME");

                $io->info('An empty .env file was created.');
            }
        }

        if (false === $this->phpPath) {
            throw new \RuntimeException('The php executable could not be found.');
        }

        $php = [
            $this->phpPath,
            '-dmemory_limit='.\ini_get('memory_limit'),
        ];

        if (OutputInterface::VERBOSITY_DEBUG === $output->getVerbosity()) {
            $php[] = '-ddisplay_errors=-1';
            $php[] = '-ddisplay_startup_errors=-1';
        }

        $commands = [
            ['contao:install-web-dir', $this->webDir, '--env=prod'],
            ['assets:install', $this->webDir, '--symlink', '--relative', '--env=prod'],
            ['contao:install', $this->webDir, '--env=prod'],
            ['contao:symlinks', $this->webDir, '--env=prod'],
            ['cache:clear', '--no-warmup', '--env=prod'],
            ['cache:clear', '--no-warmup', '--env=dev'],
            ['cache:warmup', '--env=prod'],
        ];

        $commandFlags = array_filter([
            $output->isDecorated() ? '--ansi' : '--no-ansi',
            $this->getVerbosityFlag($output),
        ]);

        foreach ($commands as $command) {
            $this->executeCommand(array_merge($php, [$this->consolePath], $command, $commandFlags), $output);
        }

        $io->info('Done! Please open the Contao install tool or run the contao:migrate command to make sure the database is up-to-date.');

        return 0;
    }

    /**
     * Executes a console command in its own process and streams the output.
     */
    private function executeCommand(array $command, OutputInterface $output): void
    {
        /** @var Process $process */
        $process = ($this->createProcessHandler)($command);

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
