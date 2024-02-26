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
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class ContaoFilesystemLoaderWarmerTest extends TestCase
{
    public function testIsOptional(): void
    {
        $filesystemLoaderWarmer = $this->getContaoFilesystemLoaderWarmer();

        $this->assertTrue($filesystemLoaderWarmer->isOptional());
    }

    public function testWarmsUpContaoFilesystemLoader(): void
    {
        $filesystemLoader = $this->createMock(ContaoFilesystemLoader::class);
        $filesystemLoader
            ->expects($this->once())
            ->method('warmUp')
            ->with(false) // should just warm up and not force a refresh
        ;

        $filesystemLoaderWarmer = $this->getContaoFilesystemLoaderWarmer($filesystemLoader);
        $filesystemLoaderWarmer->warmUp();
    }

    public function testWritesIdeAutoCompletionFile(): void
    {
        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->method('getInheritanceChains')
            ->willReturn([
                'a' => [
                    '/templates/a.html.twig' => '@Contao_Global/templates/a.html.twig',
                    '/some/place/contao/templates/a.html.twig' => '@Contao_App/a.html.twig',
                ],
                'b' => [
                    '/templates/b.html.twig' => '@Contao_Global/b.html.twig',
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
                                ['namespace' => 'Contao', 'path' => '../../some/place/contao/templates'],
                                ['namespace' => 'Contao_App', 'path' => '../../some/place/contao/templates'],
                            ],
                        ];

                        $this->assertJson($json);
                        $this->assertSame($expectedData, json_decode($json, true, 512, JSON_THROW_ON_ERROR));

                        return true;
                    },
                ),
            )
        ;

        $warmer = $this->getContaoFilesystemLoaderWarmer($loader, 'dev', $filesystem);
        $warmer->warmUp();
    }

    public function testDoesNotWriteAutoCompletionFileInProd(): void
    {
        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->expects($this->never())
            ->method('getInheritanceChains')
        ;

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->expects($this->never())
            ->method('dumpFile')
        ;

        $warmer = $this->getContaoFilesystemLoaderWarmer($loader, 'prod', $filesystem);
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

        $warmer = $this->getContaoFilesystemLoaderWarmer(null, 'dev', $filesystem);
        $warmer->warmUp();
    }

    private function getContaoFilesystemLoaderWarmer(ContaoFilesystemLoader $filesystemLoader = null, string $environment = null, Filesystem $filesystem = null): ContaoFilesystemLoaderWarmer
    {
        return new ContaoFilesystemLoaderWarmer(
            $filesystemLoader ?? $this->createMock(ContaoFilesystemLoader::class),
            '/cache',
            $environment ?? 'prod',
            $filesystem ?? $this->createMock(Filesystem::class),
        );
    }
}
