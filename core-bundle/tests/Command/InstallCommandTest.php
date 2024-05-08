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
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class InstallCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $fs = new Filesystem();
        $fs->remove($this->getTempDir().'/files_test');
        $fs->remove($this->getTempDir().'/public/assets');
        $fs->remove($this->getTempDir().'/public/share');
        $fs->remove($this->getTempDir().'/public/system');
        $fs->remove($this->getTempDir().'/system/cache');
        $fs->remove($this->getTempDir().'/system/config');
        $fs->remove($this->getTempDir().'/system/modules/.gitignore');
        $fs->remove($this->getTempDir().'/system/themes');
        $fs->remove($this->getTempDir().'/system/tmp');
        $fs->remove($this->getTempDir().'/templates');

        $this->resetStaticProperties([Terminal::class]);

        parent::tearDown();
    }

    public function testCreatesTheContaoFolders(): void
    {
        $command = new InstallCommand($this->getTempDir(), 'files', $this->getTempDir().'/public/assets/images');
        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $output = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertStringContainsString(' * public/assets/css', $output);
        $this->assertStringContainsString(' * public/assets/images', $output);
        $this->assertStringContainsString(' * public/assets/js', $output);
        $this->assertStringContainsString(' * public/system', $output);
        $this->assertStringContainsString(' * system/cache', $output);
        $this->assertStringContainsString(' * system/config', $output);
        $this->assertStringContainsString(' * system/tmp', $output);
        $this->assertStringContainsString(' * templates', $output);
    }

    public function testHandlesCustomFilesAndImagesPaths(): void
    {
        $command = new InstallCommand($this->getTempDir(), 'files_test', $this->getTempDir().'/public/assets/images_test');
        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertStringContainsString(' * files_test', $display);
        $this->assertStringContainsString(' * public/assets/images_test', $display);
    }
}
