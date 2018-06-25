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

use Contao\ManagerBundle\Api\ManagerConfig;
use Contao\ManagerBundle\Command\InstallWebDirCommand;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class InstallWebDirCommandTest extends ContaoTestCase
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
    public function setUp(): void
    {
        parent::setUp();

        $this->command = new InstallWebDirCommand();
        $this->command->setApplication($this->mockApplication());

        $this->filesystem = new Filesystem();

        $this->webFiles = Finder::create()
            ->files()
            ->ignoreDotFiles(false)
            ->in(__DIR__.'/../../src/Resources/skeleton/web')
        ;

        $ref = new \ReflectionClass(InstallWebDirCommand::class);
        $prop = $ref->getProperty('optionalFiles');
        $prop->setAccessible(true);
        $this->optionalFiles = $prop->getValue($this->command);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $this->filesystem->remove($this->getTempDir().'/web');
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf('Contao\ManagerBundle\Command\InstallWebDirCommand', $this->command);
    }

    public function testNameAndArguments(): void
    {
        $this->assertSame('contao:install-web-dir', $this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasArgument('path'));
    }

    public function testCommandRegular(): void
    {
        foreach ($this->webFiles as $file) {
            $this->assertFileNotExists($this->getTempDir().'/web/'.$file->getFilename());
        }

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->getTempDir()]);

        foreach ($this->webFiles as $file) {
            $this->assertFileExists($this->getTempDir().'/web/'.$file->getRelativePathname());

            $expectedString = file_get_contents($file->getPathname());
            $expectedString = str_replace(['{root-dir}', '{vendor-dir}'], ['../app', '../vendor'], $expectedString);

            $this->assertStringEqualsFile($this->getTempDir().'/web/'.$file->getRelativePathname(), $expectedString);
        }
    }

    public function testCommandDoesNotOverrideOptionals(): void
    {
        foreach ($this->webFiles as $file) {
            $this->filesystem->dumpFile($this->getTempDir().'/web/'.$file->getRelativePathname(), 'foobar-content');
        }

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->getTempDir()]);

        foreach ($this->webFiles as $file) {
            if (\in_array($file->getRelativePathname(), $this->optionalFiles, true)) {
                $this->assertStringEqualsFile($this->getTempDir().'/web/'.$file->getFilename(), 'foobar-content');
            } else {
                $this->assertStringNotEqualsFile($this->getTempDir().'/web/'.$file->getFilename(), 'foobar-content');
            }
        }
    }

    public function testCommandRemovesInstallPhp(): void
    {
        $this->filesystem->dumpFile($this->getTempDir().'/web/install.php', 'foobar-content');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->getTempDir()]);

        $this->assertFileNotExists($this->getTempDir().'/web/install.php');
    }

    public function testInstallsAppDevByDefault(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->getTempDir()]);

        $this->assertFileExists($this->getTempDir().'/web/app_dev.php');
    }

    public function testNotInstallsAppDevOnProd(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->getTempDir(), '--no-dev' => true]);

        $this->assertFileNotExists($this->getTempDir().'/web/app_dev.php');
    }

    public function testAccesskeyFromArgument(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->getTempDir(), '--user' => 'foo', '--password' => 'bar']);

        $this->assertFileExists($this->getTempDir().'/.env');

        $env = (new Dotenv())->parse(file_get_contents($this->getTempDir().'/.env'), $this->getTempDir().'/.env');

        $this->assertArrayHasKey('APP_DEV_ACCESSKEY', $env);
        $this->assertTrue(password_verify('foo:bar', $env['APP_DEV_ACCESSKEY']));
    }

    public function testAccesskeyFromInput(): void
    {
        $questionHelper = $this->createMock(QuestionHelper::class);

        $questionHelper
            ->expects($this->exactly(2))
            ->method('ask')
            ->withConsecutive(
                [
                    $this->isInstanceOf(InputInterface::class),
                    $this->isInstanceOf(OutputInterface::class),
                    $this->callback(
                        function (Question $question) {
                            return 'Please enter a username:' === $question->getQuestion() && !$question->isHidden();
                        }
                    ),
                ],
                [
                    $this->isInstanceOf(InputInterface::class),
                    $this->isInstanceOf(OutputInterface::class),
                    $this->callback(
                        function (Question $question) {
                            return 'Please enter a password:' === $question->getQuestion() && $question->isHidden();
                        }
                    ),
                ]
            )
            ->willReturnOnConsecutiveCalls('foo', 'bar')
        ;

//        $questionHelper
//            ->expects($this->once())
//            ->method('setHidden')
//            ->with(true)
//            ->willReturnSelf()
//        ;

        $this->command->getHelperSet()->set($questionHelper, 'question');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->getTempDir(), '--password' => null]);

        $this->assertFileExists($this->getTempDir().'/.env');

        $env = (new Dotenv())->parse(file_get_contents($this->getTempDir().'/.env'), $this->getTempDir().'/.env');

        $this->assertArrayHasKey('APP_DEV_ACCESSKEY', $env);
        $this->assertTrue(password_verify('foo:bar', $env['APP_DEV_ACCESSKEY']));
    }

    public function testAccesskeyWithUserFromInput(): void
    {
        $questionHelper = $this->createMock(QuestionHelper::class);

        $questionHelper
            ->expects($this->once())
            ->method('ask')
            ->with(
                $this->isInstanceOf(InputInterface::class),
                $this->isInstanceOf(OutputInterface::class),
                $this->callback(
                    function (Question $question) {
                        return 'Please enter a password:' === $question->getQuestion() && $question->isHidden();
                    }
                )
            )
            ->willReturn('bar')
        ;

        $this->command->getHelperSet()->set($questionHelper, 'question');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->getTempDir(), '--user' => 'foo']);

        $env = (new Dotenv())->parse(file_get_contents($this->getTempDir().'/.env'), $this->getTempDir().'/.env');

        $this->assertArrayHasKey('APP_DEV_ACCESSKEY', $env);
        $this->assertTrue(password_verify('foo:bar', $env['APP_DEV_ACCESSKEY']));
    }

    public function testAccesskeyWithoutUserFromInput(): void
    {
        QuestionHelper::disableStty();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Must have username and password');

        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs(['foo']);
        $commandTester->execute(['path' => $this->getTempDir(), '--password' => 'bar']);
    }

    public function testAccesskeyAppendToDotEnv(): void
    {
        $this->filesystem->dumpFile($this->getTempDir().'/.env', 'FOO=bar');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['path' => $this->getTempDir(), '--user' => 'foo', '--password' => 'bar']);

        $this->assertFileExists($this->getTempDir().'/.env');

        $env = (new Dotenv())->parse(file_get_contents($this->getTempDir().'/.env'), $this->getTempDir().'/.env');

        $this->assertArrayHasKey('FOO', $env);
        $this->assertSame('bar', $env['FOO']);
        $this->assertArrayHasKey('APP_DEV_ACCESSKEY', $env);
        $this->assertTrue(password_verify('foo:bar', $env['APP_DEV_ACCESSKEY']));
    }

    /**
     * Mocks the application.
     *
     * @param ManagerConfig|null $config
     *
     * @return Application
     */
    private function mockApplication(ManagerConfig $config = null): Application
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', 'foobar');
        $container->set('filesystem', new Filesystem());

        $kernel = $this->createMock(ContaoKernel::class);

        $kernel
            ->method('getContainer')
            ->willReturn($container)
        ;

        if (null !== $config) {
            $kernel
                ->expects($this->atLeastOnce())
                ->method('getManagerConfig')
                ->willReturn($config)
            ;
        }

        $container->set('kernel', $kernel);

        $application = new Application($kernel);
        $application->setCatchExceptions(true);

        return $application;
    }
}
