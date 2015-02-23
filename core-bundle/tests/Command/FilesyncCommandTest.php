<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Command;

use Contao\CoreBundle\Command\FilesyncCommand;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Tests the FilesyncCommand class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class FilesyncCommandTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $command = new FilesyncCommand('contao:filesync');

        $this->assertInstanceOf('Contao\CoreBundle\Command\FilesyncCommand', $command);
    }

    /**
     * Tests the output if not locked.
     */
    public function testOutputNotLocked()
    {
        $command = new FilesyncCommand('contao:filesync');
        $tester  = new CommandTester($command);

        $tester->execute([]);

        $this->assertEquals("  Synchronization complete (see mylog.log).\n", $tester->getDisplay());
    }

    /**
     * Tests the output if not locked.
     */
    public function testOutputIfLocked()
    {
        $lock = new LockHandler('contao:filesync');
        $lock->lock();
        $command = new FilesyncCommand('contao:filesync');
        $tester  = new CommandTester($command);

        $tester->execute([]);

        $this->assertEquals("The command is already running in another process.\n", $tester->getDisplay());
    }
}
