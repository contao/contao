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

    private function getContaoFilesystemLoaderWarmer(ContaoFilesystemLoader|null $filesystemLoader = null): ContaoFilesystemLoaderWarmer
    {
        return new ContaoFilesystemLoaderWarmer(
            $filesystemLoader ?? $this->createStub(ContaoFilesystemLoader::class),
        );
    }
}
