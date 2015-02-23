<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Command;

use Contao\CoreBundle\Command\AutomatorCommand;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Tests the AutomatorCommand class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class AutomatorCommandTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $command = new AutomatorCommand('contao:automator');

        $this->assertInstanceOf('Contao\CoreBundle\Command\AutomatorCommand', $command);
    }

    /**
     * Tests the output if not locked.
     */
    public function testOutputNotLocked()
    {
        $command = new AutomatorCommand('contao:automator');
        $command->setApplication($this->getDefaultApplication());
        $tester  = new CommandTester($command);

        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $command->getHelper('question');
        $helper->setInputStream($this->getInputStream("\n"));

        $tester->execute(array('command' => $command->getName()));

        $this->assertEquals($this->getTaskSelection(), $tester->getDisplay());
    }

    /**
     * Tests the output if locked.
     */
    public function testOutputLocked()
    {
        $lock = new LockHandler('contao:automator');
        $lock->lock();

        $command = new AutomatorCommand('contao:automator');
        $command->setApplication($this->getDefaultApplication());
        $tester  = new CommandTester($command);

        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $command->getHelper('question');
        $helper->setInputStream($this->getInputStream("\n"));

        $tester->execute(array('command' => $command->getName()));

        $this->assertEquals("The command is already running in another process.\n", $tester->getDisplay());
        $lock->release();
    }

    /**
     * Tests the output if invalid task given.
     */
    public function testInvalidTask()
    {
        $command = new AutomatorCommand('contao:automator');
        $command->setApplication($this->getDefaultApplication());
        $tester  = new CommandTester($command);

        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $command->getHelper('question');
        $helper->setInputStream($this->getInputStream("4800\n"));

        $code = $tester->execute(array('command' => $command->getName()));

        $this->assertEquals(1, $code);
        $this->assertEquals(
            $this->getTaskSelection()
            . 'Value "4800" is invalid (see help contao:automator)'
            . "\n",
            $tester->getDisplay()
        );
    }

    /**
     * Tests the output if invalid task given.
     */
    public function testTaskViaArgument()
    {
        $command = new AutomatorCommand('contao:automator');
        $command->setApplication($this->getDefaultApplication());
        $tester  = new CommandTester($command);

        $code = $tester->execute(
            [
                'command'   => $command->getName(),
                'task'      => 'checkForUpdates'
            ]
        );

        $this->assertEquals(0, $code);
    }

    /**
     * Tests the output if invalid task given.
     */
    public function testInvalidTaskViaArgument()
    {
        $command = new AutomatorCommand('contao:automator');
        $command->setApplication($this->getDefaultApplication());
        $tester  = new CommandTester($command);

        $code = $tester->execute(
            [
                'command'   => $command->getName(),
                'task'      => 'nirvanaCommand'
            ]
        );

        $this->assertEquals(1, $code);
        $this->assertEquals(
            'Value "nirvanaCommand" is invalid (see help contao:automator)'
            . "\n",
            $tester->getDisplay()
        );
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }

    protected function getDefaultApplication()
    {
        $application = new Application();
        $application->setCatchExceptions(true);

        return $application;
    }

    protected function getTaskSelection()
    {
        return "Please select a task:
  [0 ] checkForUpdates
  [1 ] purgeSearchTables
  [2 ] purgeUndoTable
  [3 ] purgeVersionTable
  [4 ] purgeSystemLog
  [5 ] purgeImageCache
  [6 ] purgeScriptCache
  [7 ] purgePageCache
  [8 ] purgeSearchCache
  [9 ] purgeInternalCache
  [10] purgeTempFolder
  [11] generateXmlFiles
  [12] purgeXmlFiles
  [13] generateSitemap
  [14] rotateLogs
  [15] generateSymlinks
  [16] generateInternalCache
  [17] generateConfigCache
  [18] generateDcaCache
  [19] generateLanguageCache
  [20] generateDcaExtracts
  [21] generatePackageCache
 > ";
    }
}
