<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Tools\IsolatedTests;

use Contao\CoreBundle\Tests\PhpunitExtension\GlobalStateWatcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class RunTestsIsolatedCommand extends Command
{
    protected static $defaultName = 'contao:run-tests-isolated';
    protected static $defaultDescription = 'Runs the unit tests isolated from each other.';

    /**
     * @var string|false
     */
    private $phpPath;

    private string $projectDir;

    public function __construct(string $projectDir)
    {
        parent::__construct();

        $this->projectDir = $projectDir;
        $this->phpPath = (new PhpExecutableFinder())->find();
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addOption('depth', null, InputOption::VALUE_REQUIRED, '1 for test classes, 2 for test methods, 3 for every single provider data set', '3');

        $this->setHelp(
            <<<'EOT'
                The command runs each unit test completely isolated from the others, starting
                a new PHPUnit process for each test class, method, or data set. This gives us
                "real" isolation rather than shared state, unlike the PHPUnit option
                --process-isolation does.
                EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (false === $this->phpPath) {
            throw new \RuntimeException('The php executable could not be found.');
        }

        $depth = (int) $input->getOption('depth');

        if ($depth < 1 || $depth > 3) {
            throw new \InvalidArgumentException('--depth must be an integer between 1 and 3');
        }

        $php = [
            $this->phpPath,
            '-dmemory_limit='.\ini_get('memory_limit'),
        ];

        if (OutputInterface::VERBOSITY_DEBUG === $output->getVerbosity()) {
            $php[] = '-ddisplay_errors=-1';
            $php[] = '-ddisplay_startup_errors=-1';
        }

        $phpunit = $this->projectDir.'/vendor/bin/phpunit';

        $listOutput = new BufferedOutput();

        $this->executeCommand(array_merge($php, [$phpunit, '--list-tests']), $listOutput);

        $tests = [[], [], []];

        foreach (preg_split('/\r?\n/', $listOutput->fetch()) as $line) {
            if (preg_match('/^ - (\S+)(::[^\s#"]+)(.*)$/', $line, $matches)) {
                $tests[0][] = $matches[1];

                if ($depth > 1) {
                    $tests[1][] = $matches[1].$matches[2];
                }

                if ($matches[3] && $depth > 2) {
                    $tests[2][] .= $matches[1].$matches[2].' with data set '.$matches[3];
                }
            }
        }

        $tests = array_values(array_unique(array_merge(...$tests)));

        $commandFlags = array_filter([
            $output->isDecorated() ? '--colors=always' : '',
            $this->getVerbosityFlag($output),
        ]);

        $failedTests = [];

        foreach ($tests as $test) {
            // Skip if the whole class, or the test with all data sets failed already
            foreach ($failedTests as $failedTest) {
                if (0 === strncmp($test, $failedTest, \strlen($failedTest))) {
                    continue 2;
                }
            }

            $filter = preg_quote($test);
            $output->writeln("> vendor/bin/phpunit --filter '".str_replace("'", '\'"\'"\'', $filter)."'");

            $buffer = new BufferedOutput();

            try {
                $this->executeCommand(array_merge($php, [$phpunit, '--extensions', GlobalStateWatcher::class, '--filter', $filter], $commandFlags), $buffer);

                // Clear previously written line
                $output->write("\e[1A\e[K");
            } catch (\Throwable $e) {
                $failedTests[] = $test;
                $output->writeln($buffer->fetch());
            }
        }

        if ($failedTests) {
            $output->writeln("<error>Failed executing tests:\n - ".implode("\n - ", $failedTests).'</error>');

            return 1;
        }

        $output->writeln('<info>Good job! All tests green 💪.</info>');

        return 0;
    }

    /**
     * Executes a console command in its own process and streams the output.
     */
    private function executeCommand(array $command, OutputInterface $output): void
    {
        $process = new Process($command);

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
