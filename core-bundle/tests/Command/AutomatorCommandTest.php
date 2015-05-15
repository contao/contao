<?php

/*
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
use Symfony\Component\Console\Helper\QuestionHelper;
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

        $this->assertInstanceOf('Contao\\CoreBundle\\Command\\AutomatorCommand', $command);
    }

    /**
     * Tests the output.
     */
    public function testOutput()
    {
        $command = new AutomatorCommand('contao:automator');
        $command->setApplication($this->getApplication());
        $command->setFramework($this->mockContaoFramework());

        $tester = new CommandTester($command);

        /** @var QuestionHelper $helper */
        $helper = $command->getHelper('question');
        $helper->setInputStream($this->getStreamFromInput("\n"));

        $code = $tester->execute(['command' => $command->getName()]);

        $this->assertEquals(0, $code);
        $this->assertContains('Please select a task:', $tester->getDisplay());
        $this->assertContains('[10]', $tester->getDisplay());
    }

    /**
     * Tests the __toString() method.
     */
    public function testToString()
    {
        $command = new AutomatorCommand('contao:automator');

        $this->assertContains('The name of the task:', $command->__toString());
    }

    /**
     * Tests the lock.
     */
    public function testLock()
    {
        $lock = new LockHandler('contao:automator');
        $lock->lock();

        $command = new AutomatorCommand('contao:automator');
        $command->setApplication($this->getApplication());
        $command->setFramework($this->mockContaoFramework());

        $tester = new CommandTester($command);

        /** @var QuestionHelper $helper */
        $helper = $command->getHelper('question');
        $helper->setInputStream($this->getStreamFromInput("\n"));

        $code = $tester->execute(['command' => $command->getName()]);

        $this->assertEquals(1, $code);
        $this->assertEquals("The command is already running in another process.\n", $tester->getDisplay());

        $lock->release();
    }

    /**
     * Tests an argument.
     */
    public function testArgument()
    {
        $command = new AutomatorCommand('contao:automator');
        $command->setApplication($this->getApplication());
        $command->setFramework($this->mockContaoFramework());

        $tester = new CommandTester($command);

        $code = $tester->execute([
            'command' => $command->getName(),
            'task'    => 'checkForUpdates',
        ]);

        $this->assertEquals(0, $code);
    }

    /**
     * Tests an invalid task.
     */
    public function testInvalidTask()
    {
        $command = new AutomatorCommand('contao:automator');
        $command->setApplication($this->getApplication());
        $command->setFramework($this->mockContaoFramework());

        $tester = new CommandTester($command);

        /** @var QuestionHelper $helper */
        $helper = $command->getHelper('question');
        $helper->setInputStream($this->getStreamFromInput("4800\n"));

        $code = $tester->execute(['command' => $command->getName()]);

        $this->assertEquals(1, $code);
        $this->assertContains('Value "4800" is invalid (see help contao:automator)', $tester->getDisplay());
    }

    /**
     * Tests an invalid argument.
     */
    public function testInvalidArgument()
    {
        $command = new AutomatorCommand('contao:automator');
        $command->setApplication($this->getApplication());
        $command->setFramework($this->mockContaoFramework());

        $tester = new CommandTester($command);

        $code = $tester->execute([
            'command' => $command->getName(),
            'task'    => 'fooBar',
        ]);

        $this->assertEquals(1, $code);
        $this->assertContains('Invalid task "fooBar" (see help contao:automator)', $tester->getDisplay());
    }

    /**
     * Converts a string into a stream.
     *
     * @param string $input The input string
     *
     * @return resource The stream
     */
    private function getStreamFromInput($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }

    /**
     * Returns the application object.
     *
     * @return Application The application object
     */
    private function getApplication()
    {
        $application = new Application();
        $application->setCatchExceptions(true);

        return $application;
    }
}
