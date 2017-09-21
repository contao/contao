<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\FilesyncCommand;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\LockHandler;

class FilesyncCommandTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $command = new FilesyncCommand('contao:filesync');

        $this->assertInstanceOf('Contao\CoreBundle\Command\FilesyncCommand', $command);
        $this->assertSame('contao:filesync', $command->getName());
    }

    public function testOutputsTheConfirmationMessage(): void
    {
        $command = new FilesyncCommand('contao:filesync');
        $command->setFramework($this->mockContaoFramework());

        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $this->assertSame(0, $code);
        $this->assertContains('Synchronization complete (see sync.log).', $tester->getDisplay());
    }

    public function testIsLockedWhileRunning(): void
    {
        $lock = new LockHandler('contao:filesync');
        $lock->lock();

        $command = new FilesyncCommand('contao:filesync');
        $command->setFramework($this->mockContaoFramework());

        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $this->assertSame(1, $code);
        $this->assertContains('The command is already running in another process.', $tester->getDisplay());

        $lock->release();
    }
}
