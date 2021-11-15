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
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ContaoFilesystemLoaderWarmerTest extends TestCase
{
    public function testWarmUp(): void
    {
        $projectDir = '/project/dir';

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

        $warmer = new ContaoFilesystemLoaderWarmer(
            $loader,
            $this->mockTemplateLocator($themePaths, $resourcesPaths),
            $projectDir,
            'prod'
        );

        $warmer->warmUp();
    }

    public function testIsMandatoryWarmer(): void
    {
        $warmer = new ContaoFilesystemLoaderWarmer(
            $this->createMock(ContaoFilesystemLoader::class),
            $this->createMock(TemplateLocator::class),
            '/project/dir',
            'prod'
        );

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

        $warmer = new ContaoFilesystemLoaderWarmer(
            $loader,
            $this->mockTemplateLocator(),
            'project/dir',
            'any'
        );

        $warmer->refresh();
    }

    public function testRefreshOnKernelRequestIfInDevMode(): void
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

        $warmer = new ContaoFilesystemLoaderWarmer(
            $loader,
            $this->mockTemplateLocator(),
            'project/dir',
            'dev'
        );

        $warmer->onKernelRequest($this->createMock(RequestEvent::class));
    }

    public function testDoesNotRefreshOnKernelRequestIfNotInDevMode(): void
    {
        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->expects($this->never())
            ->method('clear')
        ;

        $loader
            ->expects($this->never())
            ->method('addPath')
        ;

        $warmer = new ContaoFilesystemLoaderWarmer(
            $loader,
            $this->mockTemplateLocator(),
            'project/dir',
            'prod'
        );

        $warmer->onKernelRequest($this->createMock(RequestEvent::class));
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
}
