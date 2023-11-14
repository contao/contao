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
use Contao\ManagerBundle\ContaoManager\ApiCommand\SetDotEnvCommand;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class SetDotEnvCommandTest extends ContaoTestCase
{
    private Filesystem $filesystem;

    private string $tempdir;

    private string $tempfile;

    private SetDotEnvCommand $command;

    #[\Override]
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

        $this->command = new SetDotEnvCommand($application);
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->filesystem->remove($this->tempdir);
    }

    public function testHasCorrectNameAndArguments(): void
    {
        $this->assertSame('dot-env:set', $this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasArgument('key'));
        $this->assertTrue($this->command->getDefinition()->getArgument('key')->isRequired());
        $this->assertTrue($this->command->getDefinition()->hasArgument('value'));
        $this->assertTrue($this->command->getDefinition()->getArgument('value')->isRequired());
    }

    public function testCreatesDotEnvFileIfItDoesNotExist(): void
    {
        $this->assertFileDoesNotExist($this->tempfile);

        $tester = new CommandTester($this->command);
        $tester->execute(['key' => 'FOO', 'value' => '$BAR']);

        $this->assertSame('', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
        $this->assertFileExists(substr($this->tempfile, 0, -6));
        $this->assertSame('', file_get_contents(substr($this->tempfile, 0, -6)));
        $this->assertFileExists($this->tempfile);
        $this->assertSame("FOO='\$BAR'\n", file_get_contents($this->tempfile));
    }

    public function testAppendsToDotEnvFileIfItExists(): void
    {
        $this->filesystem->dumpFile($this->tempfile, "BAR='FOO'\n");

        $tester = new CommandTester($this->command);
        $tester->execute(['key' => 'FOO', 'value' => '$BAR']);

        $this->assertSame('', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
        $this->assertFileExists($this->tempfile);
        $this->assertSame("BAR='FOO'\nFOO='\$BAR'\n", file_get_contents($this->tempfile));
    }

    public function testOverwriteDotEnvIfKeyExists(): void
    {
        $this->filesystem->dumpFile($this->tempfile, "BAR='FOO'\nFOO='FOO'\n");

        $tester = new CommandTester($this->command);
        $tester->execute(['key' => 'FOO', 'value' => '$BAR']);

        $this->assertSame('', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
        $this->assertFileExists($this->tempfile);
        $this->assertSame("BAR='FOO'\nFOO='\$BAR'\n", file_get_contents($this->tempfile));
    }

    public function testEscapesShellArguments(): void
    {
        $tester = new CommandTester($this->command);
        $tester->execute(['key' => 'FOO', 'value' => "UNESCAPED ' STRING"]);

        $this->assertSame('', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
        $this->assertFileExists($this->tempfile);
        $this->assertSame("FOO=\"UNESCAPED ' STRING\"\n", file_get_contents($this->tempfile));
    }
}
