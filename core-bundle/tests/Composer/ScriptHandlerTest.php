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
        $method = new \ReflectionMethod($this->handler, 'getBinDir');
        $method->setAccessible(true);

        $this->assertEquals($expected, $method->invokeArgs($this->handler, [$this->getComposerEvent($extra)]));
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
        $method = new \ReflectionMethod($this->handler, 'getWebDir');
        $method->setAccessible(true);

        $this->assertEquals($expected, $method->invokeArgs($this->handler, [$this->getComposerEvent($extra)]));
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
     * Tests the getVerbosityFlag() method.
     */
    public function testGetVerbosityFlag()
    {
        $method = new \ReflectionMethod($this->handler, 'getVerbosityFlag');
        $method->setAccessible(true);

        $this->assertEquals(
            '',
            $method->invokeArgs($this->handler, [$this->getComposerEvent()])
        );

        $this->assertEquals(
            ' -v',
            $method->invokeArgs($this->handler, [$this->getComposerEvent([], 'isVerbose')])
        );

        $this->assertEquals(
            ' -vv',
            $method->invokeArgs($this->handler, [$this->getComposerEvent([], 'isVeryVerbose')])
        );

        $this->assertEquals(
            ' -vvv',
            $method->invokeArgs($this->handler, [$this->getComposerEvent([], 'isDebug')])
        );
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
     * @param array       $extra
     * @param string|null $method
     *
     * @return Event
     */
    private function getComposerEvent(array $extra = [], $method = null)
    {
        $package = $this->mockPackage($extra);

        return new Event('', $this->mockComposer($package), $this->mockIO($method));
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
     * @param string|null $method
     *
     * @return IOInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockIO($method = null)
    {
        $io = $this->getMock('Composer\IO\IOInterface');

        if (null !== $method) {
            $io->expects($this->any())->method($method)->willReturn(true);
        }

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
