<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\Downloader\DownloadManager;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Script\Event;
use Contao\CoreBundle\Composer\ScriptHandler;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ScriptHandlerTest extends TestCase
{
    /**
     * @var ScriptHandler
     */
    private $handler;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->handler = new ScriptHandler();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGeneratesARandomSecretIfTheConfigurationFileDoesNotExist(): void
    {
        $this->assertRandomSecretDoesNotExist();

        $this->handler->generateRandomSecret(
            $this->mockComposerEvent(
                [
                    'incenteev-parameters' => [
                        'file' => $this->getFixturesDir().'/app/config/parameters.yml',
                    ],
                ]
            )
        );

        $this->assertRandomSecretIsValid();
    }

    public function testDoesNotGenerateARandomSecretIfTheConfigurationFileExists(): void
    {
        $this->assertRandomSecretDoesNotExist();

        touch($this->getFixturesDir().'/app/config/parameters.yml');

        $this->handler->generateRandomSecret(
            $this->mockComposerEvent(
                [
                    'incenteev-parameters' => [
                        'file' => __DIR__.'/../Fixtures/app/config/parameters.yml',
                    ],
                ]
            )
        );

        unlink($this->getFixturesDir().'/app/config/parameters.yml');

        $this->assertRandomSecretDoesNotExist();
    }

    public function testDoesNotGenerateARandomSecretIfNoConfigurationFileIsDefined(): void
    {
        $this->assertRandomSecretDoesNotExist();

        $this->handler->generateRandomSecret($this->mockComposerEvent());

        $this->assertRandomSecretDoesNotExist();

        $this->handler->generateRandomSecret(
            $this->mockComposerEvent(
                [
                    'incenteev-parameters' => [],
                ]
            )
        );

        $this->assertRandomSecretDoesNotExist();
    }

    /**
     * @dataProvider binDirProvider
     */
    public function testReadsTheBinDirFromTheConfiguration(array $extra, string $expected): void
    {
        $method = new \ReflectionMethod($this->handler, 'getBinDir');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invokeArgs($this->handler, [$this->mockComposerEvent($extra)]));
    }

    public function binDirProvider(): \Generator
    {
        yield [
            [],
            'app',
        ];

        yield [
            ['symfony-app-dir' => 'foo/bar'],
            'foo/bar',
        ];

        yield [
            ['symfony-var-dir' => __DIR__],
            'bin',
        ];

        yield [
            ['symfony-var-dir' => __DIR__, 'symfony-bin-dir' => 'app'],
            'app',
        ];
    }

    /**
     * @dataProvider webDirProvider
     */
    public function testReadsTheWebDirFromTheConfiguration(array $extra, string $expected): void
    {
        $method = new \ReflectionMethod($this->handler, 'getWebDir');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invokeArgs($this->handler, [$this->mockComposerEvent($extra)]));
    }

    public function webDirProvider(): \Generator
    {
        yield [
            [],
            'web',
        ];

        yield [
            ['symfony-web-dir' => 'foo/bar'],
            'foo/bar',
        ];
    }

    public function testHandlesTheVerbosityFlag(): void
    {
        $method = new \ReflectionMethod($this->handler, 'getVerbosityFlag');
        $method->setAccessible(true);

        $this->assertSame(
            '',
            $method->invokeArgs($this->handler, [$this->mockComposerEvent()])
        );

        $this->assertSame(
            ' -v',
            $method->invokeArgs($this->handler, [$this->mockComposerEvent([], 'isVerbose')])
        );

        $this->assertSame(
            ' -vv',
            $method->invokeArgs($this->handler, [$this->mockComposerEvent([], 'isVeryVerbose')])
        );

        $this->assertSame(
            ' -vvv',
            $method->invokeArgs($this->handler, [$this->mockComposerEvent([], 'isDebug')])
        );
    }

    private function assertRandomSecretDoesNotExist(): void
    {
        $this->assertEmpty(getenv(ScriptHandler::RANDOM_SECRET_NAME));
    }

    private function assertRandomSecretIsValid(): void
    {
        $this->assertNotFalse(getenv(ScriptHandler::RANDOM_SECRET_NAME));
        $this->assertGreaterThanOrEqual(64, \strlen(getenv(ScriptHandler::RANDOM_SECRET_NAME)));
    }

    private function mockComposerEvent(array $extra = [], string $method = null): Event
    {
        $package = $this->mockPackage($extra);

        return new Event('', $this->mockComposer($package), $this->mockIO($method));
    }

    /**
     * @return Composer|MockObject
     */
    private function mockComposer(PackageInterface $package): Composer
    {
        $composer = $this->createMock(Composer::class);
        $composer
            ->method('getConfig')
            ->willReturn($this->createMock(Config::class))
        ;

        $composer
            ->method('getDownloadManager')
            ->willReturn($this->createMock(DownloadManager::class))
        ;

        $composer
            ->method('getPackage')
            ->willReturn($package)
        ;

        return $composer;
    }

    /**
     * @return IOInterface|MockObject
     */
    private function mockIO(string $method = null): IOInterface
    {
        $io = $this->createMock(IOInterface::class);

        if (null !== $method) {
            $io->method($method)->willReturn(true);
        }

        return $io;
    }

    /**
     * @return PackageInterface|MockObject
     */
    private function mockPackage(array $extras = []): PackageInterface
    {
        $package = $this->createMock(PackageInterface::class);
        $package
            ->method('getTargetDir')
            ->willReturn('')
        ;

        $package
            ->method('getName')
            ->willReturn('foo/bar')
        ;

        $package
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
