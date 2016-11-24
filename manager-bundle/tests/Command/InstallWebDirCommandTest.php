<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\Command;

use Contao\ManagerBundle\Command\InstallWebDirCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Tests the InstallWebDirCommand class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Yanick Witschi <https://github.com/toflar>
 */
class InstallWebDirCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InstallWebDirCommand
     */
    private $command;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $tmpdir;

    /**
     * @var Finder
     */
    private $webFiles;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new InstallWebDirCommand();
        $this->filesystem = new Filesystem();
        $this->tmpdir = sys_get_temp_dir() . '/' . uniqid('InstallWebDirCommand_', false);
        $this->webFiles = Finder::create()->files()->ignoreDotFiles(false)->in(__DIR__ . '/../../src/Resources/web');
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf(InstallWebDirCommand::class, $this->command);
    }

    /**
     * Tests the command name.
     */
    public function testNameAndArguments()
    {
        $this->assertEquals('contao:install-web-dir', $this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasArgument('path'));
        $this->assertTrue($this->command->getDefinition()->hasOption('force'));
    }

    public function testCommandRegular()
    {
        foreach ($this->webFiles as $file) {
            $this->assertFileNotExists($this->tmpdir . '/web/' . $file->getFilename());
        }

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->tmpdir]);

        foreach ($this->webFiles as $file) {
            $this->assertFileExists($this->tmpdir . '/web/' . $file->getFilename());

            $expectedString = file_get_contents($file->getPathname());

            $expectedString = str_replace(
                ['{root-dir}', '{vendor-dir}'],
                ['../app', '../vendor'],
                $expectedString
            );

            $this->assertStringEqualsFile($this->tmpdir . '/web/' . $file->getFilename(), $expectedString);
        }
    }

    public function testCommandDoesNothingWithoutForce()
    {
        foreach ($this->webFiles as $file) {
            $this->filesystem->dumpFile($this->tmpdir . '/web/' . $file->getFilename(), 'foobar-content');
        }

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->tmpdir]);

        foreach ($this->webFiles as $file) {
            $this->assertStringEqualsFile($this->tmpdir . '/web/' . $file->getFilename(), 'foobar-content');
        }
    }

    public function testCommandOverwritesWithForce()
    {
        foreach ($this->webFiles as $file) {
            $this->filesystem->dumpFile($this->tmpdir . '/web/' . $file->getFilename(), 'foobar-content');
        }

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->tmpdir, '--force' => null]);

        foreach ($this->webFiles as $file) {
            // Assert
            $expectedString = str_replace(
                ['{root-dir}', '{vendor-dir}'],
                ['../app', '../vendor'],
                $file->getContents()
            );

            $this->assertStringEqualsFile($this->tmpdir . '/web/' . $file->getFilename(), $expectedString);
        }
    }
}
