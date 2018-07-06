<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\ContaoManager\ApiCommand;

use Contao\ManagerBundle\Api\Application;
use Contao\ManagerBundle\ContaoManager\ApiCommand\GetDotEnvCommand;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class GetDotEnvCommandTest extends ContaoTestCase
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $tempdir;

    /**
     * @var string
     */
    private $tempfile;

    /**
     * @var GetDotEnvCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->tempdir = $this->getTempDir();
        $this->tempfile = $this->tempdir.'/.env';

        $application = $this->createMock(Application::class);

        $application
            ->method('getProjectDir')
            ->willReturn($this->tempdir)
        ;

        $this->command = new GetDotEnvCommand($application);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->filesystem->remove($this->tempdir);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf('Contao\ManagerBundle\ContaoManager\ApiCommand\GetDotEnvCommand', $this->command);
    }

    public function testHasCorrectNameAndArguments(): void
    {
        $this->assertSame('dot-env:get', $this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasArgument('key'));
        $this->assertFalse($this->command->getDefinition()->getArgument('key')->isRequired());
    }

    public function testReadsDotEnvFile(): void
    {
        $this->filesystem->dumpFile($this->tempfile, 'FOO=BAR');

        $tester = new CommandTester($this->command);
        $tester->execute(['key' => 'FOO']);

        $this->assertSame('BAR', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testOutputsNothingIfDotEnvDoesNotExist(): void
    {
        $tester = new CommandTester($this->command);
        $tester->execute(['key' => 'FOO']);

        $this->assertSame('', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testOutputsNothingIfKeyDoesNotExist(): void
    {
        $this->filesystem->dumpFile($this->tempfile, 'BAR=FOO');

        $tester = new CommandTester($this->command);
        $tester->execute(['key' => 'FOO']);

        $this->assertSame('', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testOutputsAllKeysIfNoArgumentIsGiven(): void
    {
        $this->filesystem->dumpFile($this->tempfile, "FOO=BAR\nBAR=BAZ");

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $this->assertSame('{"FOO":"BAR","BAR":"BAZ"}', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }
}
