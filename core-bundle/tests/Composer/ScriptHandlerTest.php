<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Script\Event;
use Contao\CoreBundle\Composer\ScriptHandler;
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the ScriptHandler class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ScriptHandlerTest extends TestCase
{
    /**
     * @var ScriptHandler
     */
    private $handler;

    public function setUp()
    {
        $this->handler = new ScriptHandler();
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\\CoreBundle\\Composer\\ScriptHandler', $this->handler);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGeneratesRandomSecret()
    {
        $this->assertRandomSecretDoesNotExist();

        $this->handler->generateRandomSecret(
            $this->getComposerEvent(
                [
                    'incenteev-parameters' => [
                        'file' => __DIR__ . '/../Fixtures/app/config/parameters.yml'
                    ]
                ]
            )
        );

        $this->assertRandomSecretIsValid();
    }

    /**
     * @runInSeparateProcess
     */
    public function testGeneratesRandomSecretArray()
    {
        $this->assertRandomSecretDoesNotExist();

        $this->handler->generateRandomSecret(
            $this->getComposerEvent(
                [
                    'incenteev-parameters' => [
                        [
                            'file' => __DIR__ . '/../Fixtures/app/config/parameters.yml'
                        ],
                        [
                            'file' => __DIR__ . '/../Fixtures/app/config/test.yml'
                        ]
                    ]
                ]
            )
        );

        $this->assertRandomSecretIsValid();
    }

    /**
     * @runInSeparateProcess
     */
    public function testGeneratesNoRandomSecretWithoutFileConfig()
    {
        $this->assertRandomSecretDoesNotExist();

        $this->handler->generateRandomSecret(
            $this->getComposerEvent([])
        );

        $this->assertRandomSecretDoesNotExist();

        $this->handler->generateRandomSecret(
            $this->getComposerEvent(
                [
                    'incenteev-parameters' => []
                ]
            )
        );

        $this->assertRandomSecretDoesNotExist();
    }

    /**
     * @runInSeparateProcess
     */
    public function testGeneratesNoRandomSecretIfFileExists()
    {
        $this->assertRandomSecretDoesNotExist();

        touch(__DIR__ . '/../Fixtures/app/config/parameters.yml');

        $this->handler->generateRandomSecret(
            $this->getComposerEvent(
                [
                    'incenteev-parameters' => [
                        'file' => __DIR__ . '/../Fixtures/app/config/parameters.yml'
                    ]
                ]
            )
        );

        unlink(__DIR__ . '/../Fixtures/app/config/parameters.yml');

        $this->assertRandomSecretDoesNotExist();
    }

    /**
     * @runInSeparateProcess
     */
    public function testGeneratesNoRandomSecretIfFileExistsArray()
    {
        $this->assertRandomSecretDoesNotExist();

        touch(__DIR__ . '/../Fixtures/app/config/parameters.yml');

        $this->handler->generateRandomSecret(
            $this->getComposerEvent(
                [
                    'incenteev-parameters' => [
                        [
                            'file' => __DIR__ . '/../Fixtures/app/config/parameters.yml'
                        ],
                        [
                            'file' => __DIR__ . '/../Fixtures/app/config/test.yml'
                        ]
                    ]
                ]
            )
        );

        unlink(__DIR__ . '/../Fixtures/app/config/parameters.yml');

        $this->assertRandomSecretDoesNotExist();
    }

    /**
     * Asserts that the random secret environment variable is not set.
     */
    private function assertRandomSecretDoesNotExist()
    {
        $this->assertEmpty(getenv(ScriptHandler::RANDOM_SECRET_NAME));
    }

    /**
     * Asserts that the random secret environment variable is set and valid.
     */
    private function assertRandomSecretIsValid()
    {
        $this->assertNotFalse(getenv(ScriptHandler::RANDOM_SECRET_NAME));
        $this->assertGreaterThanOrEqual(64, strlen(getenv(ScriptHandler::RANDOM_SECRET_NAME)));
    }

    /**
     * @param array $extra
     *
     * @return Event
     */
    private function getComposerEvent(array $extra = [])
    {
        $package = $this->mockPackage($extra);

        return new Event('', $this->mockComposer($package), $this->mockIO());
    }

    /**
     * @param PackageInterface $package
     *
     * @return Composer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockComposer(PackageInterface $package)
    {
        $config          = $this->getMock('Composer\\Config');
        $downloadManager = $this->getMock('Composer\\Downloader\\DownloadManager', [], [], '', false);
        $composer        = $this->getMock('Composer\\Composer', ['getConfig', 'getDownloadManager', 'getPackage']);

        $composer
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($config)
        ;

        $composer
            ->expects($this->any())
            ->method('getDownloadManager')
            ->willReturn($downloadManager)
        ;

        $composer
            ->expects($this->any())
            ->method('getPackage')
            ->willReturn($package)
        ;

        return $composer;
    }

    /**
     * @return IOInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockIO()
    {
        $io = $this->getMock('Composer\\IO\\IOInterface');

        $io
            ->expects($this->any())
            ->method('isVerbose')
            ->willReturn(true)
        ;

        $io
            ->expects($this->any())
            ->method('isVeryVerbose')
            ->willReturn(true)
        ;

        return $io;
    }

    /**
     * @param array $extras
     *
     * @return PackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockPackage(array $extras = [])
    {
        $package = $this->getMock('Composer\\Package\\PackageInterface');

        $package
            ->expects($this->any())
            ->method('getTargetDir')
            ->willReturn('')
        ;

        $package
            ->expects($this->any())
            ->method('getName')
            ->willReturn('foo/bar')
        ;

        $package
            ->expects($this->any())
            ->method('getPrettyName')
            ->willReturn('foo/bar')
        ;

        $package
            ->expects(empty($extras) ? $this->any() : $this->atLeastOnce())
            ->method('getExtra')
            ->willReturn($extras)
        ;

        return $package;
    }
}
