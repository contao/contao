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

/**
 * Tests the InstallWebDirCommand class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InstallWebDirCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InstallWebDirCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new InstallWebDirCommand('contao:install-web-dir');
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        parent::tearDown();

        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/web');
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\ManagerBundle\Command\InstallWebDirCommand', $this->command);
    }

    /**
     * Tests the command name.
     */
    public function testName()
    {
        $this->assertEquals('contao:install-web-dir', $this->command->getName());
    }

    public function testCommandRegular()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => __DIR__]);

        $output = $commandTester->getDisplay();

        foreach (['.htaccess', 'app.php', 'install.php'] as $file) {
            $this->assertContains('Added the ' . $file . ' file.', $output);
            $this->assertFileExists(__DIR__ . '/web/' . $file);

            $expectedString = file_get_contents(__DIR__ . '/../../src/Resources/web/' . $file);

            $expectedString = str_replace(
                ['{root-dir}', '{vendor-dir}'],
                ['../app', '../vendor'],
                $expectedString
            );

            $this->assertStringEqualsFile(__DIR__ . '/web/' . $file, $expectedString);
        }
    }

    public function testCommandDoesNothingWithoutForce()
    {
        // Files added
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => __DIR__]);

        // Temporarily edit app.php with dummy content to see if --force works
        $appPath = __DIR__ . '/../../src/Resources/web/app.php';
        $original = file_get_contents($appPath);
        $tmp = $original . PHP_EOL . 'foobar-content';
        file_put_contents($appPath, $tmp);

        // Test without --force
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => __DIR__]);

        // Assert
        $expectedString = str_replace(
            ['{root-dir}', '{vendor-dir}'],
            ['../app', '../vendor'],
            $original // Should still be the same as the original
        );

        $this->assertStringEqualsFile(__DIR__ . '/web/app.php', $expectedString);

        // Restore
        file_put_contents($appPath, $original);
    }

    public function testCommandOverwritesWithForce()
    {
        // Files added
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => __DIR__]);

        // Temporarily edit app.php with dummy content to see if --force works
        $appPath = __DIR__ . '/../../src/Resources/web/app.php';
        $original = file_get_contents($appPath);
        $tmp = $original . PHP_EOL . 'foobar-content';
        file_put_contents($appPath, $tmp);

        // Test with --force
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => __DIR__, '--force' => null]);

        // Assert
        $expectedString = str_replace(
            ['{root-dir}', '{vendor-dir}'],
            ['../app', '../vendor'],
            $tmp // Should match the new content
        );

        $this->assertStringEqualsFile(__DIR__ . '/web/app.php', $expectedString);

        // Restore
        file_put_contents($appPath, $original);
    }
}
