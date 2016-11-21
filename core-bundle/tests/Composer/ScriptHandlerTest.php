<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Script\Event;
use Contao\CoreBundle\Composer\ScriptHandler;

/**
 * Tests the ScriptHandler class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * @preserveGlobalState disabled
 */
class ScriptHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScriptHandler
     */
    private $handler;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->handler = new ScriptHandler();
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Composer\ScriptHandler', $this->handler);
    }

    /**
     * Tests generating a random secret.
     *
     * @runInSeparateProcess
     */
    public function testGeneratesRandomSecret()
    {
        $this->assertRandomSecretDoesNotExist();

        $this->handler->generateRandomSecret(
            $this->getComposerEvent(
                [
                    'incenteev-parameters' => [
                        'file' => __DIR__.'/../Fixtures/app/config/parameters.yml',
                    ],
                ]
            )
        );

        $this->assertRandomSecretIsValid();
    }

    /**
     * Tests generating a random secret with an array of configuration files.
     *
     * @runInSeparateProcess
     */
    public function testGeneratesRandomSecretArray()
    {
        $this->assertRandomSecretDoesNotExist();

        $this->handler->generateRandomSecret(
            $this->getComposerEvent(
                [
                    'incenteev-parameters' => [
                        ['file' => __DIR__.'/../Fixtures/app/config/parameters.yml'],
                        ['file' => __DIR__.'/../Fixtures/app/config/test.yml'],
                    ],
                ]
            )
        );

        $this->assertRandomSecretIsValid();
    }

    /**
     * Tests that no secret is generated if there is no configuration file.
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
                    'incenteev-parameters' => [],
                ]
            )
        );

        $this->assertRandomSecretDoesNotExist();
    }

    /**
     * Tests that no secret is generated if the configuration file exists.
     */
    public function testGeneratesNoRandomSecretIfFileExists()
    {
        $this->assertRandomSecretDoesNotExist();

        touch(__DIR__.'/../Fixtures/app/config/parameters.yml');

        $this->handler->generateRandomSecret(
            $this->getComposerEvent(
                [
                    'incenteev-parameters' => [
                        'file' => __DIR__.'/../Fixtures/app/config/parameters.yml',
                    ],
                ]
            )
        );

        unlink(__DIR__.'/../Fixtures/app/config/parameters.yml');

        $this->assertRandomSecretDoesNotExist();
    }

    /**
     * Tests that no secret is generated if at least one of multiple configuration files exists.
     */
    public function testGeneratesNoRandomSecretIfFileExistsArray()
    {
        $this->assertRandomSecretDoesNotExist();

        touch(__DIR__.'/../Fixtures/app/config/parameters.yml');

        $this->handler->generateRandomSecret(
            $this->getComposerEvent(
                [
                    'incenteev-parameters' => [
                        ['file' => __DIR__.'/../Fixtures/app/config/parameters.yml'],
                        ['file' => __DIR__.'/../Fixtures/app/config/test.yml'],
                    ],
                ]
            )
        );

        unlink(__DIR__.'/../Fixtures/app/config/parameters.yml');

        $this->assertRandomSecretDoesNotExist();
    }

    /**
     * Tests the getBinDir() method.
     *
     * @param array  $extra
     * @param string $expected
     *
     * @dataProvider binDirProvider
     */
    public function testGetBinDir(array $extra, $expected)
    {
        $reflection = new \ReflectionClass('Contao\CoreBundle\Composer\ScriptHandler');
        $method = $reflection->getMethod('getBinDir');
        $method->setAccessible(true);

        $event = $this->getComposerEvent($extra);

        $this->assertEquals($expected, $method->invokeArgs($reflection, [$event]));
    }

    /**
     * Provides the bin dir data.
     *
     * @return array
     */
    public function binDirProvider()
    {
        return [
            [
                [],
                'app',
            ],
            [
                ['symfony-app-dir' => 'foo/bar'],
                'foo/bar',
            ],
            [
                ['symfony-var-dir' => __DIR__],
                'bin',
            ],
            [
                [
                    'symfony-var-dir' => __DIR__,
                    'symfony-bin-dir' => 'app',
                ],
                'app',
            ],
        ];
    }

    /**
     * Tests the getWebDir() method.
     *
     * @param array  $extra
     * @param string $expected
     *
     * @dataProvider webDirProvider
     */
    public function testGetWebDir(array $extra, $expected)
    {
        $reflection = new \ReflectionClass('Contao\CoreBundle\Composer\ScriptHandler');
        $method = $reflection->getMethod('getWebDir');
        $method->setAccessible(true);

        $event = $this->getComposerEvent($extra);

        $this->assertEquals($expected, $method->invokeArgs($reflection, [$event]));
    }

    /**
     * Provides the web dir data.
     *
     * @return array
     */
    public function webDirProvider()
    {
        return [
            [
                [],
                'web',
            ],
            [
                ['symfony-web-dir' => 'foo/bar'],
                'foo/bar',
            ],
        ];
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
     * Returns the composer event object.
     *
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
     * Mocks the Composer object.
     *
     * @param PackageInterface $package
     *
     * @return Composer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockComposer(PackageInterface $package)
    {
        $config = $this->getMock('Composer\Config');
        $downloadManager = $this->getMock('Composer\Downloader\DownloadManager', [], [], '', false);
        $composer = $this->getMock('Composer\Composer', ['getConfig', 'getDownloadManager', 'getPackage']);

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
     * Mocks the IO object.
     *
     * @return IOInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockIO()
    {
        $io = $this->getMock('Composer\IO\IOInterface');

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
     * Mocks the package object.
     *
     * @param array $extras
     *
     * @return PackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockPackage(array $extras = [])
    {
        $package = $this->getMock('Composer\Package\PackageInterface');

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
