<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\ThemeTemplatesListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoaderWarmer;
use Symfony\Component\Translation\TranslatorInterface;

class ThemeTemplatesListenerTest extends TestCase
{
    public function testRefreshesCache(): void
    {
        $filesystemLoaderWarmer = $this->createMock(ContaoFilesystemLoaderWarmer::class);
        $filesystemLoaderWarmer
            ->expects($this->once())
            ->method('refresh')
        ;

        $translator = $this->createMock(TranslatorInterface::class);

        $listener = new ThemeTemplatesListener($filesystemLoaderWarmer, $translator);

        $this->assertSame('templates/foo/bar', $listener('templates/foo/bar'));
    }

    public function testThrowsFriendlyErrorMessageIfPathIsInvalid(): void
    {
        $filesystemLoaderWarmer = $this->createMock(ContaoFilesystemLoaderWarmer::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->with(
                'ERR.invalidThemeTemplatePath',
                ['templates/invalid.path/b_ar', '._'],
                'contao_default',
            )
            ->willReturn('<message>')
        ;

        $listener = new ThemeTemplatesListener($filesystemLoaderWarmer, $translator);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('<message>');

        $listener('templates/invalid.path/b_ar');
    }
}
