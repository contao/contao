<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Command;

use Contao\CoreBundle\Command\DumpTwigIDEFileCommand;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\IDE\NamespaceLookupFileGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DumpTwigIDEFileCommandTest extends TestCase
{
    public function testWritesFile(): void
    {
        $namespaceLookupFileGenerator = $this->createMock(NamespaceLookupFileGenerator::class);
        $namespaceLookupFileGenerator
            ->expects($this->once())
            ->method('write')
            ->with('/project/dir/foo')
        ;

        $command = new DumpTwigIDEFileCommand($namespaceLookupFileGenerator, '/project/dir');

        $tester = new CommandTester($command);
        $tester->execute(['dir' => 'foo']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('The namespace lookup file was written to "foo/ide-twig.json".', $tester->getDisplay());
    }
}
