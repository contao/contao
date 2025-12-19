<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\DumpTwigIdeFileCommand;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Ide\NamespaceLookupFileGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DumpTwigIdeFileCommandTest extends TestCase
{
    public function testWritesFileAtDefaultLocation(): void
    {
        $command = $this->getCommand('/project/var/build/contao-ide');

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString(
            'The namespace lookup file was written to "var/build/contao-ide/ide-twig.json".',
            $this->getNormalizedDisplay($tester),
        );
    }

    public function testWritesFileToCustomLocation(): void
    {
        $command = $this->getCommand('/project/foo');

        $tester = new CommandTester($command);
        $tester->execute(['dir' => 'foo']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString(
            'The namespace lookup file was written to "foo/ide-twig.json".',
            $this->getNormalizedDisplay($tester),
        );
    }

    private function getCommand(string $expectedWriteDir): DumpTwigIdeFileCommand
    {
        $namespaceLookupFileGenerator = $this->createMock(NamespaceLookupFileGenerator::class);
        $namespaceLookupFileGenerator
            ->expects($this->once())
            ->method('write')
            ->with($expectedWriteDir)
        ;

        return new DumpTwigIdeFileCommand(
            $namespaceLookupFileGenerator,
            '/project/var/build',
            '/project',
        );
    }

    private function getNormalizedDisplay(CommandTester $tester): string
    {
        $output = str_replace(PHP_EOL, '', $tester->getDisplay());

        return preg_replace('/  +/', ' ', $output);
    }
}
