<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\Composer;

use Composer\Composer;
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableRepositoryInterface;
use Composer\Script\Event;
use Composer\Util\Filesystem;
use Contao\ManagerBundle\Composer\ScriptHandler;

/**
 * Tests the ScriptHandler class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ScriptHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\ManagerBundle\Composer\ScriptHandler', new ScriptHandler());
    }

    public function testInitializeApplicationMethodExists()
    {
        $this->assertTrue(method_exists(ScriptHandler::class, 'initializeApplication'));
    }

    public function testAddAppDirectory()
    {
        ScriptHandler::addAppDirectory();
    }

    public function testAddConsoleEntryPoint()
    {
        $tmpdir = sys_get_temp_dir() . '/' . uniqid('ScriptHandler_', false);
        $fs = new Filesystem();
        $event = new Event('', $this->mockComposer(dirname(dirname(__DIR__))), $this->getMock(IOInterface::class));

        $fs->ensureDirectoryExists($tmpdir);
        chdir($tmpdir);

        $content = str_replace('../../../../', '../', file_get_contents(__DIR__ . '/../../bin/contao-console'));

        ScriptHandler::addConsoleEntryPoint($event);

        $this->assertFileExists($tmpdir . '/bin/console');
        $this->assertStringEqualsFile($tmpdir . '/bin/console', $content);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddConsoleEntryPointExceptionWhenFileIsMissing()
    {
        $event = new Event('', $this->mockComposer(sys_get_temp_dir()), $this->getMock(IOInterface::class));

        chdir(sys_get_temp_dir());

        ScriptHandler::addConsoleEntryPoint($event);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testAddConsoleEntryPointExceptionWhenWritingFails()
    {
        $tmpdir = sys_get_temp_dir() . '/' . uniqid('ScriptHandler_', false);
        $fs = new Filesystem();
        $event = new Event('', $this->mockComposer(dirname(dirname(__DIR__))), $this->getMock(IOInterface::class));

        $fs->ensureDirectoryExists($tmpdir . '/bin');
        chdir($tmpdir);

        touch($tmpdir . '/bin/console');
        chmod($tmpdir . '/bin/console', 0000);

        ScriptHandler::addConsoleEntryPoint($event);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testAddConsoleEntryPointExceptionWhenPackageIsNotFound()
    {
        $event = new Event(
            '',
            $this->mockComposer(dirname(dirname(__DIR__)), 'foo/bar'),
            $this->getMock(IOInterface::class)
        );

        ScriptHandler::addConsoleEntryPoint($event);
    }

    /**
     * Mocks the Composer object.
     *
     * @param string $consolePath
     * @param string $packageName
     *
     * @return Composer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockComposer($consolePath, $packageName = 'contao/manager-bundle')
    {
        $package = $this->getMock(PackageInterface::class);
        $repository = $this->getMock(WritableRepositoryInterface::class);
        $repositoryManager = $this->getMockBuilder(RepositoryManager::class)->disableOriginalConstructor()->getMock();
        $installationManager = $this->getMock(InstallationManager::class);
        $composer = $this->getMock(Composer::class, ['getRepositoryManager', 'getInstallationManager']);

        $package
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn($packageName)
        ;

        $repository
            ->expects($this->any())
            ->method('getPackages')
            ->willReturn([$package])
        ;

        $repositoryManager
            ->expects($this->any())
            ->method('getLocalRepository')
            ->willReturn($repository)
        ;

        $installationManager
            ->expects($this->any())
            ->method('getInstallPath')
            ->with($package)
            ->willReturn($consolePath)
        ;

        $composer
            ->expects($this->any())
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager)
        ;

        $composer
            ->expects($this->any())
            ->method('getInstallationManager')
            ->willReturn($installationManager)
        ;

        return $composer;
    }
}
