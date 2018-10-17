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
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;

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
        $fs->remove($this->getTempDir().'/system/modules/.gitignore');
        $fs->remove($this->getTempDir().'/system/tmp');
        $fs->remove($this->getTempDir().'/templates');
        $fs->remove($this->getTempDir().'/web/share');
        $fs->remove($this->getTempDir().'/web/system');
    }

    public function testCreatesTheContaoFolders(): void
    {
        $container = $this->mockContainer($this->getTempDir());
        $container->set('filesystem', new Filesystem());

        $command = new InstallCommand('contao:install');
        $command->setContainer($container);

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
        $container = $this->mockContainer($this->getTempDir());
        $container->setParameter('contao.upload_path', 'files_test');
        $container->setParameter('contao.image.target_dir', $this->getTempDir().'/assets/images_test');
        $container->set('filesystem', new Filesystem());

        $command = new InstallCommand('contao:install');
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertContains(' * files_test', $display);
        $this->assertContains(' * assets/images_test', $display);
    }

    public function testIsLockedWhileRunning(): void
    {
        $tmpDir = sys_get_temp_dir().'/'.md5($this->getTempDir());

        if (!is_dir($tmpDir)) {
            (new Filesystem())->mkdir($tmpDir);
        }

        $factory = new Factory(new FlockStore($tmpDir));

        $lock = $factory->createLock('contao:install');
        $lock->acquire();

        $command = new InstallCommand('contao:install');
        $command->setContainer($this->mockContainer($this->getTempDir()));

        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $this->assertSame(1, $code);
        $this->assertContains('The command is already running in another process.', $tester->getDisplay());

        $lock->release();
    }
}
