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
    private Filesystem $filesystem;
    private string $tempdir;
    private string $tempfile;
    private GetDotEnvCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->tempdir = $this->getTempDir();
        $this->tempfile = $this->tempdir.'/.env.local';

        $application = $this->createMock(Application::class);
        $application
            ->method('getProjectDir')
            ->willReturn($this->tempdir)
        ;

        $this->command = new GetDotEnvCommand($application);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->filesystem->remove($this->tempdir);
    }

    public function testHasCorrectNameAndArguments(): void
    {
        $this->assertSame('dot-env:get', $this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasArgument('key'));
        $this->assertFalse($this->command->getDefinition()->getArgument('key')->isRequired());
    }

    public function testReadsDotEnvLocalFile(): void
    {
        $this->filesystem->dumpFile(substr($this->tempfile, 0, -6), '');
        $this->filesystem->dumpFile($this->tempfile, 'FOO=BAR');

        $tester = new CommandTester($this->command);
        $tester->execute(['key' => 'FOO']);

        $this->assertSame('BAR', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testReadsDotEnvFile(): void
    {
        $this->filesystem->dumpFile(substr($this->tempfile, 0, -6), 'FOO=BAR');

        $tester = new CommandTester($this->command);
        $tester->execute(['key' => 'FOO']);

        $this->assertSame('BAR', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testReadsDotEnvLocalFileIfBothExist(): void
    {
        $this->filesystem->dumpFile(substr($this->tempfile, 0, -6), 'FOO=BAR');
        $this->filesystem->dumpFile($this->tempfile, 'FOO=BAZ');

        $tester = new CommandTester($this->command);
        $tester->execute(['key' => 'FOO']);

        $this->assertSame('BAZ', $tester->getDisplay());
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
        $this->filesystem->dumpFile(substr($this->tempfile, 0, -6), '');
        $this->filesystem->dumpFile($this->tempfile, "FOO=BAR\nBAR=BAZ");

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $this->assertSame('{"FOO":"BAR","BAR":"BAZ"}', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testOutputsAllKeysFromBothFiles(): void
    {
        $this->filesystem->dumpFile(substr($this->tempfile, 0, -6), "FOO=BAR\nBAZ=BAR");
        $this->filesystem->dumpFile($this->tempfile, "FOO=BAR\nBAR=BAZ");

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $this->assertSame('{"FOO":"BAR","BAZ":"BAR","BAR":"BAZ"}', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }
}
