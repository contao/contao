<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Command;

use Contao\ManagerBundle\Command\InstallWebDirCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Tests the InstallWebDirCommand class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Yanick Witschi <https://github.com/toflar>
 */
class InstallWebDirCommandTest extends TestCase
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
     * @var array
     */
    private $optionalFiles;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new InstallWebDirCommand();
        $this->command->setApplication($this->mockApplication());

        $this->filesystem = new Filesystem();
        $this->tmpdir = sys_get_temp_dir().'/'.uniqid('InstallWebDirCommand_', true);
        $this->webFiles = Finder::create()->files()->ignoreDotFiles(false)->in(__DIR__.'/../../src/Resources/web');

        $ref = new \ReflectionClass(InstallWebDirCommand::class);
        $prop = $ref->getProperty('optionalFiles');
        $prop->setAccessible(true);
        $this->optionalFiles = $prop->getValue($this->command);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        $this->filesystem->remove($this->tmpdir);
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
    public function testNameAndArguments()
    {
        $this->assertSame('contao:install-web-dir', $this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasArgument('path'));
    }

    /**
     * Tests the command.
     */
    public function testCommandRegular()
    {
        foreach ($this->webFiles as $file) {
            $this->assertFileNotExists($this->tmpdir.'/web/'.$file->getFilename());
        }

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->tmpdir]);

        foreach ($this->webFiles as $file) {
            $this->assertFileExists($this->tmpdir.'/web/'.$file->getRelativePathname());

            $expectedString = file_get_contents($file->getPathname());
            $expectedString = str_replace(['{root-dir}', '{vendor-dir}'], ['../app', '../vendor'], $expectedString);

            $this->assertStringEqualsFile($this->tmpdir.'/web/'.$file->getRelativePathname(), $expectedString);
        }
    }

    /**
     * Tests that the command does not override optional optional files.
     */
    public function testCommandDoesNotOverrideOptionals()
    {
        foreach ($this->webFiles as $file) {
            $this->filesystem->dumpFile($this->tmpdir.'/web/'.$file->getRelativePathname(), 'foobar-content');
        }

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->tmpdir]);

        foreach ($this->webFiles as $file) {
            if (\in_array($file->getRelativePathname(), $this->optionalFiles, true)) {
                $this->assertStringEqualsFile($this->tmpdir.'/web/'.$file->getFilename(), 'foobar-content');
            } else {
                $this->assertStringNotEqualsFile($this->tmpdir.'/web/'.$file->getFilename(), 'foobar-content');
            }
        }
    }

    /**
     * Tests that the install.php is removed from web directory.
     */
    public function testCommandRemovesInstallPhp()
    {
        $this->filesystem->dumpFile($this->tmpdir.'/web/install.php', 'foobar-content');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->tmpdir]);

        $this->assertFileNotExists($this->tmpdir.'/web/install.php');
    }

    /**
     * Tests that the install.php is removed from web directory.
     */
    public function testInstallsAppDevByDefault()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->tmpdir]);

        $this->assertFileExists($this->tmpdir.'/web/app_dev.php');
    }

    /**
     * Tests that the install.php is removed from web directory.
     */
    public function testNotInstallsAppDevOnProd()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->tmpdir, '--no-dev' => true]);

        $this->assertFileNotExists($this->tmpdir.'/web/app_dev.php');
    }

    /**
     * Tests setting the access key as argument.
     */
    public function testAccesskeyFromArgument()
    {
        if ('\\' === \DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('This test is skipped on Windows');
        }

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->tmpdir, '--user' => 'foo', '--password' => 'bar']);

        $this->assertFileExists($this->tmpdir.'/.env');

        $env = (new Dotenv())->parse(file_get_contents($this->tmpdir.'/.env'), $this->tmpdir.'/.env');

        $this->assertArrayHasKey('APP_DEV_ACCESSKEY', $env);
        $this->assertTrue(password_verify('foo:bar', $env['APP_DEV_ACCESSKEY']));
    }

    /**
     * Tests setting the access key interactively.
     */
    public function testAccesskeyFromInput()
    {
        if ('\\' === \DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('This test is skipped on Windows');
        }

        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs(['foo', 'bar']);
        $commandTester->execute(['path' => $this->tmpdir, '--password' => null]);

        $this->assertContains('Please enter a username:', $commandTester->getDisplay());
        $this->assertContains('Please enter a password:', $commandTester->getDisplay());

        $this->assertFileExists($this->tmpdir.'/.env');

        $env = (new Dotenv())->parse(file_get_contents($this->tmpdir.'/.env'), $this->tmpdir.'/.env');

        $this->assertArrayHasKey('APP_DEV_ACCESSKEY', $env);
        $this->assertTrue(password_verify('foo:bar', $env['APP_DEV_ACCESSKEY']));
    }

    /**
     * Tests setting the access key interactively with a given username.
     */
    public function testAccesskeyWithUserFromInput()
    {
        if ('\\' === \DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('This test is skipped on Windows');
        }

        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs(['bar']);
        $commandTester->execute(['path' => $this->tmpdir, '--user' => 'foo']);

        $this->assertNotContains('Please enter a username:', $commandTester->getDisplay());
        $this->assertContains('Please enter a password:', $commandTester->getDisplay());

        $env = (new Dotenv())->parse(file_get_contents($this->tmpdir.'/.env'), $this->tmpdir.'/.env');

        $this->assertArrayHasKey('APP_DEV_ACCESSKEY', $env);
        $this->assertTrue(password_verify('foo:bar', $env['APP_DEV_ACCESSKEY']));
    }

    /**
     * Tests setting the access key interactively without a username.
     */
    public function testAccesskeyWithoutUserFromInput()
    {
        QuestionHelper::disableStty();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Must have username and password');

        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs(['foo']);
        $commandTester->execute(['path' => $this->tmpdir, '--password' => 'bar']);
    }

    /**
     * Tests that the access key is appended to the .env file.
     */
    public function testAccesskeyAppendToDotEnv()
    {
        if ('\\' === \DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('This test is skipped on Windows');
        }

        $this->filesystem->dumpFile($this->tmpdir.'/.env', 'FOO=bar');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->tmpdir, '--user' => 'foo', '--password' => 'bar']);

        $this->assertFileExists($this->tmpdir.'/.env');

        $env = (new Dotenv())->parse(file_get_contents($this->tmpdir.'/.env'), $this->tmpdir.'/.env');

        $this->assertArrayHasKey('FOO', $env);
        $this->assertSame('bar', $env['FOO']);
        $this->assertArrayHasKey('APP_DEV_ACCESSKEY', $env);
        $this->assertTrue(password_verify('foo:bar', $env['APP_DEV_ACCESSKEY']));
    }

    /**
     * Mocks the application.
     *
     * @return Application
     */
    private function mockApplication()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', 'foobar');

        $kernel = $this->createMock(KernelInterface::class);

        $kernel
            ->method('getContainer')
            ->willReturn($container)
        ;

        $application = new Application($kernel);
        $application->setCatchExceptions(true);

        return $application;
    }
}
