<?php

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

/**
 * Tests the InstallCommand class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InstallCommandTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $tcpdfPath = $this->getRootDir().'/vendor/contao/core-bundle/src/Resources/contao/config/tcpdf.php';

        if (!file_exists($tcpdfPath)) {
            if (!file_exists(\dirname($tcpdfPath))) {
                mkdir(\dirname($tcpdfPath), 0777, true);
            }

            file_put_contents($tcpdfPath, '');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $fs = new Filesystem();

        $fs->remove($this->getRootDir().'/assets/css');
        $fs->remove($this->getRootDir().'/assets/images');
        $fs->remove($this->getRootDir().'/assets/images_test');
        $fs->remove($this->getRootDir().'/assets/js');
        $fs->remove($this->getRootDir().'/files_test');
        $fs->remove($this->getRootDir().'/system/cache');
        $fs->remove($this->getRootDir().'/system/config');
        $fs->remove($this->getRootDir().'/system/initialize.php');
        $fs->remove($this->getRootDir().'/system/modules/.gitignore');
        $fs->remove($this->getRootDir().'/system/themes');
        $fs->remove($this->getRootDir().'/system/tmp');
        $fs->remove($this->getRootDir().'/templates');
        $fs->remove($this->getRootDir().'/web/share');
        $fs->remove($this->getRootDir().'/web/system');
        $fs->remove($this->getRootDir().'/vendor/contao/core-bundle/src/Resources/contao/config/tcpdf.php');
    }

    /**
     * Tests creating the the Contao folders.
     */
    public function testCreatesTheContaoFolders()
    {
        $command = new InstallCommand(
            $this->getRootDir(),
            'files',
            $this->getRootDir().'/assets/images',
            $this->getBundlesMetadata()
        );

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

    /**
     * Tests adding a custom files and images directory.
     */
    public function testHandlesCustomFilesAndImagesPaths()
    {
        $command = new InstallCommand(
            $this->getRootDir(),
            'files_test',
            $this->getRootDir().'/assets/images_test',
            $this->getBundlesMetadata()
        );

        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertContains(' * files_test', $display);
        $this->assertContains(' * assets/images_test', $display);
    }

    /**
     * Tests adding the initialize.php file and tcpdf.php symlink.
     */
    public function testCreatesInitializeFileAndTcpdfSymlink()
    {
        $command = new InstallCommand(
            $this->getRootDir(),
            'files_test',
            $this->getRootDir().'/assets/images_test',
            $this->getBundlesMetadata()
        );

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertFileExists($this->getRootDir().'/system/initialize.php');

        // is_link() returns true even if the symlink target does not exist, therefore also check file_exists()
        $this->assertTrue(is_link($this->getRootDir().'/system/config/tcpdf.php'));
        $this->assertFileExists($this->getRootDir().'/system/config/tcpdf.php');
    }

    /**
     * @return array<string,array<string,string>>
     */
    private function getBundlesMetadata()
    {
        return [
            'ContaoCoreBundle' => [
                'path' => $this->getRootDir().'/vendor/contao/core-bundle/src',
            ],
        ];
    }
}
