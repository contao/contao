<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Loader;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoaderWarmer;
use Contao\CoreBundle\Twig\Loader\TemplateLocator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ContaoFilesystemLoaderWarmerTest extends TestCase
{
    public function testWarmUp(): void
    {
        $themePaths = [
            'my' => '/project/dir/my',
            'my_theme' => '/project/dir/my/theme',
        ];

        $resourcesPaths = [
            'FooBundle' => [
                '/vendor/r1/templates',
                '/vendor/r1/templates/sub',
            ],
            'App' => [
                '/contao/templates',
            ],
        ];

        $expectedAddPathCalls = [
            ['/project/dir/my', 'Contao_Theme_my'],
            ['/project/dir/my/theme', 'Contao_Theme_my_theme'],
            ['/project/dir/templates', 'Contao'],
            ['/project/dir/templates', 'Contao_Global', true],
            ['/vendor/r1/templates', 'Contao'],
            ['/vendor/r1/templates', 'Contao_FooBundle', true],
            ['/vendor/r1/templates/sub', 'Contao'],
            ['/vendor/r1/templates/sub', 'Contao_FooBundle', true],
            ['/contao/templates', 'Contao'],
            ['/contao/templates', 'Contao_App', true],
        ];

        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->expects($this->exactly(\count($expectedAddPathCalls)))
            ->method('addPath')
            ->withConsecutive(...$expectedAddPathCalls)
        ;

        $loader
            ->expects($this->once())
            ->method('buildInheritanceChains')
        ;

        $loader
            ->expects($this->once())
            ->method('persist')
        ;

        $locator = $this->mockTemplateLocator($themePaths, $resourcesPaths);

        $warmer = $this->getContaoFilesystemLoaderWarmer($loader, $locator);
        $warmer->warmUp();
    }

    public function testIsMandatoryWarmer(): void
    {
        $warmer = $this->getContaoFilesystemLoaderWarmer();

        $this->assertFalse($warmer->isOptional());
    }

    public function testRefresh(): void
    {
        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->expects($this->once())
            ->method('clear')
        ;

        $loader
            ->expects($this->atLeastOnce())
            ->method('addPath')
        ;

        $warmer = $this->getContaoFilesystemLoaderWarmer($loader);
        $warmer->refresh();
    }

    /**
     * @dataProvider provideRequestScenarios
     */
    public function testRefreshOnKernelRequest(RequestEvent $event, string $environment, bool $shouldRefresh): void
    {
        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->expects($shouldRefresh ? $this->once() : $this->never())
            ->method('clear')
        ;

        $loader
            ->expects($shouldRefresh ? $this->atLeastOnce() : $this->never())
            ->method('addPath')
        ;

        $warmer = $this->getContaoFilesystemLoaderWarmer($loader, null, $environment);
        $warmer->onKernelRequest($event);
    }

    public function provideRequestScenarios(): \Generator
    {
        $mainRequestEvent = $this->createMock(RequestEvent::class);
        $mainRequestEvent
            ->method('isMainRequest')
            ->willReturn(true)
        ;

        $subRequestEvent = $this->createMock(RequestEvent::class);
        $subRequestEvent
            ->method('isMainRequest')
            ->willReturn(false)
        ;

        yield 'dev env, main request' => [$mainRequestEvent, 'dev', true];
        yield 'dev env, sub request' => [$subRequestEvent, 'dev', false];
        yield 'prod env, main request' => [$mainRequestEvent, 'prod', false];
        yield 'prod env, sub request' => [$subRequestEvent, 'prod', false];
    }

    public function testWritesIdeAutoCompletionFile(): void
    {
        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->method('getInheritanceChains')
            ->willReturn([
                'a' => [
                    '/templates/a.html.twig' => '@Contao_Global/a.html.twig',
                    '/contao/templates/a.html.twig' => '@Contao_App/a.html.twig',
                ],
                'b' => [
                    '/templates/b.html.twig' => '@Contao_Global/b.html.twig',
                ],
                'foo/c' => [
                    '/templates/foo/c.html.twig' => '@Contao_Global/foo/c.html.twig',
                ],
                'bar/d' => [
                    '/vendor/demo/bar/d.html.twig' => '@Contao_DemoBundle/bar/d.html.twig',
                ],
            ])
        ;

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with(
                '/cache/contao/ide-twig.json',
                $this->callback(
                    function (string $json): bool {
                        $expectedData = [
                            'namespaces' => [
                                ['namespace' => 'Contao', 'path' => '../../templates'],
                                ['namespace' => 'Contao_Global', 'path' => '../../templates'],
                                ['namespace' => 'Contao', 'path' => '../../contao/templates'],
                                ['namespace' => 'Contao_App', 'path' => '../../contao/templates'],
                                ['namespace' => 'Contao', 'path' => '../../vendor/demo'],
                                ['namespace' => 'Contao_DemoBundle', 'path' => '../../vendor/demo'],
                            ],
                        ];

                        $this->assertJson($json);
                        $this->assertSame($expectedData, json_decode($json, true, 512, JSON_THROW_ON_ERROR));

                        return true;
                    }
                )
            )
        ;

        $warmer = $this->getContaoFilesystemLoaderWarmer($loader, null, 'dev', $filesystem);
        $warmer->warmUp();
    }

    public function testToleratesFailingWritesWhenWritingIdeAutoCompletionFile(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with('/cache/contao/ide-twig.json', $this->anything())
            ->willThrowException(new IOException('write fail'))
        ;

        $warmer = $this->getContaoFilesystemLoaderWarmer(null, null, 'dev', $filesystem);
        $warmer->warmUp();
    }

    /**
     * @return TemplateLocator&MockObject
     */
    private function mockTemplateLocator(array $themeDirectories = [], array $resourcesPaths = []): TemplateLocator
    {
        $locator = $this->createMock(TemplateLocator::class);
        $locator
            ->method('findThemeDirectories')
            ->willReturn($themeDirectories)
        ;

        $locator
            ->method('findResourcesPaths')
            ->willReturn($resourcesPaths)
        ;

        return $locator;
    }

    private function getContaoFilesystemLoaderWarmer(ContaoFilesystemLoader $loader = null, TemplateLocator $locator = null, string $environment = 'prod', Filesystem $filesystem = null): ContaoFilesystemLoaderWarmer
    {
        return new ContaoFilesystemLoaderWarmer(
            $loader ?? $this->createMock(ContaoFilesystemLoader::class),
            $locator ?? $this->createMock(TemplateLocator::class),
            '/project/dir',
            '/cache',
            $environment,
            $filesystem ?? $this->createMock(Filesystem::class),
        );
    }
}
