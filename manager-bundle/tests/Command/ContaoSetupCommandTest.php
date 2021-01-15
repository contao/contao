<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Command;

use Contao\ManagerBundle\Command\ContaoSetupCommand;
use Contao\ManagerBundle\Process\ProcessFactory;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Webmozart\PathUtil\Path;

class ContaoSetupCommandTest extends ContaoTestCase
{
    public function testIsHidden(): void
    {
        $command = new ContaoSetupCommand('project/dir', 'project/dir/web');

        $this->assertTrue($command->isHidden());
    }

    /**
     * @dataProvider provideCommands
     */
    public function testExecutesCommands(array $options, array $flags, array $phpFlags = []): void
    {
        $processes = $this->getProcessMocks();

        foreach ($processes as $process) {
            $process
                ->expects($this->once())
                ->method('setTimeout')
                ->with(500)
            ;
        }

        $phpPath = (new PhpExecutableFinder())->find();

        $this->assertStringContainsString('php', $phpPath);

        $commandFilePath = (new \ReflectionClass(ContaoSetupCommand::class))->getFileName();
        $consolePath = Path::join(Path::getDirectory($commandFilePath), '../../bin/contao-console');

        $commandArguments = [
            array_merge([$phpPath], $phpFlags, [$consolePath, 'contao:install-web-dir', '--env=prod'], $flags),
            array_merge([$phpPath], $phpFlags, [$consolePath, 'cache:clear', '--no-warmup', '--env=prod'], $flags),
            array_merge([$phpPath], $phpFlags, [$consolePath, 'cache:clear', '--no-warmup', '--env=dev'], $flags),
            array_merge([$phpPath], $phpFlags, [$consolePath, 'cache:warmup', '--env=prod'], $flags),
            array_merge([$phpPath], $phpFlags, [$consolePath, 'assets:install', 'web', '--symlink', '--relative', '--env=prod'], $flags),
            array_merge([$phpPath], $phpFlags, [$consolePath, 'contao:install', 'web', '--env=prod'], $flags),
            array_merge([$phpPath], $phpFlags, [$consolePath, 'contao:symlinks', 'web', '--env=prod'], $flags),
        ];

        $invocationCount = 0;

        $createProcessHandler = static function (array $command) use (&$invocationCount, $commandArguments, $processes): Process {
            self::assertEquals($commandArguments[$invocationCount], $command);

            return $processes[$invocationCount++];
        };

        $command = new ContaoSetupCommand('project/dir', 'project/dir/web', $createProcessHandler);

        (new CommandTester($command))->execute([], $options);

        $this->assertSame(7, $invocationCount);
    }

    public function provideCommands(): \Generator
    {
        yield 'no arguments' => [
            [],
            ['--no-ansi'],
        ];

        yield 'ansi' => [
            ['decorated' => true],
            ['--ansi'],
        ];

        yield 'normal' => [
            ['verbosity' => OutputInterface::VERBOSITY_NORMAL],
            ['--no-ansi'],
        ];

        yield 'verbose' => [
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE],
            ['--no-ansi', '-v'],
        ];

        yield 'very verbose' => [
            ['verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE],
            ['--no-ansi', '-vv'],
        ];

        yield 'debug' => [
            ['verbosity' => OutputInterface::VERBOSITY_DEBUG],
            ['--no-ansi', '-vvv'],
            ['-ddisplay_errors=-1', '-ddisplay_startup_errors=-1'],
        ];

        yield 'ansi and verbose' => [
            ['decorated' => true, 'verbosity' => OutputInterface::VERBOSITY_VERBOSE],
            ['--ansi', '-v'],
        ];
    }

    public function testThrowsIfCommandFails(): void
    {
        $command = new ContaoSetupCommand(
            'project/dir',
            'project/dir/web',
            $this->getProcessFactoryMock($this->getProcessMocks(false))
        );

        $commandTester = (new CommandTester($command));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/An error occurred while executing the ".+" command: <error>/');

        $commandTester->execute([]);
    }

    public function testDelegatesOutputOfSubProcesses(): void
    {
        $command = new ContaoSetupCommand(
            'project/dir',
            'project/dir/web',
            $this->getProcessFactoryMock()
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(
            '[output 1][output 2][output 3][output 4][output 5][output 6][output 7]'.
            "Done! Please open the Contao install tool or run contao:migrate on the command line to make sure the database is up-to-date.\n",
            $commandTester->getDisplay()
        );
    }

    /**
     * @return ProcessFactory&MockObject
     */
    private function getProcessFactoryMock(array $processes = null)
    {
        if (null === $processes) {
            $processes = $this->getProcessMocks();
        }

        $processFactory = $this->createMock(ProcessFactory::class);
        $processFactory
            ->method('create')
            ->willReturn(...$processes)
        ;

        return $processFactory;
    }

    private function getProcessMocks(bool $successful = true): array
    {
        $processes = [];

        for ($i = 1; $i <= 7; ++$i) {
            $process = $this->createMock(Process::class);
            $process
                ->method('isSuccessful')
                ->willReturn($successful)
            ;

            $process
                ->method('run')
                ->with($this->callback(
                    static function ($callable) use ($i) {
                        $callable('', "[output $i]");

                        return true;
                    }
                ))
            ;

            $process
                ->method('getErrorOutput')
                ->willReturn('<error>')
            ;

            $processes[] = $process;
        }

        return $processes;
    }
}
