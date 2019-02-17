<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\InstallCommand;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\LockInterface;

class InstallCommandTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $fs = new Filesystem();
        $fs->remove($this->getTempDir().'/assets/css');
        $fs->remove($this->getTempDir().'/assets/images');
        $fs->remove($this->getTempDir().'/assets/images_test');
        $fs->remove($this->getTempDir().'/assets/js');
        $fs->remove($this->getTempDir().'/files_test');
        $fs->remove($this->getTempDir().'/system/cache');
        $fs->remove($this->getTempDir().'/system/config');
        $fs->remove($this->getTempDir().'/system/initialize.php');
        $fs->remove($this->getTempDir().'/system/modules/.gitignore');
        $fs->remove($this->getTempDir().'/system/themes');
        $fs->remove($this->getTempDir().'/system/tmp');
        $fs->remove($this->getTempDir().'/templates');
        $fs->remove($this->getTempDir().'/web/share');
        $fs->remove($this->getTempDir().'/web/system');
    }

    public function testCreatesTheContaoFolders(): void
    {
        $command = $this->mockCommand('files', $this->getTempDir().'/assets/images');
        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $output = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertContains(' * templates', $output);
        $this->assertContains(' * web/system', $output);
        $this->assertContains(' * assets/css', $output);
        $this->assertContains(' * assets/images', $output);
        $this->assertContains(' * assets/js', $output);
        $this->assertContains(' * system/cache', $output);
        $this->assertContains(' * system/config', $output);
        $this->assertContains(' * system/tmp', $output);
    }

    public function testHandlesCustomFilesAndImagesPaths(): void
    {
        $command = $this->mockCommand('files_test', $this->getTempDir().'/assets/images_test');
        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertContains(' * files_test', $display);
        $this->assertContains(' * assets/images_test', $display);
    }

    public function testIsLockedWhileRunning(): void
    {
        $command = $this->mockCommand('files', $this->getTempDir().'/assets/images', true);
        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $this->assertSame(1, $code);
        $this->assertContains('The command is already running in another process.', $tester->getDisplay());
    }

    private function mockCommand(string $uploadPath, string $imageDir, bool $isLocked = false): InstallCommand
    {
        $lock = $this->createMock(LockInterface::class);
        $lock
            ->expects($this->once())
            ->method('acquire')
            ->willReturn(!$isLocked)
        ;

        $lock
            ->expects($isLocked ? $this->never() : $this->once())
            ->method('release')
        ;

        return new InstallCommand($this->getTempDir(), $uploadPath, $imageDir, $lock);
    }
}
