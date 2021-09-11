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
use Contao\CoreBundle\Exception\InvalidThemePathException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoaderWarmer;
use Contao\CoreBundle\Twig\Loader\Theme;
use Symfony\Contracts\Translation\TranslatorInterface;

class ThemeTemplatesListenerTest extends TestCase
{
    public function testRefreshesCache(): void
    {
        $filesystemLoaderWarmer = $this->createMock(ContaoFilesystemLoaderWarmer::class);
        $filesystemLoaderWarmer
            ->expects($this->once())
            ->method('refresh')
        ;

        $listener = $this->getListener($filesystemLoaderWarmer);

        $this->assertSame('templates/foo/bar', $listener('templates/foo/bar'));
    }

    public function testThrowsFriendlyErrorMessageIfPathIsInvalid(): void
    {
        $theme = $this->createMock(Theme::class);
        $theme
            ->method('generateSlug')
            ->with('<bad-path>')
            ->willThrowException(new InvalidThemePathException('<bad-path>', ['.', '_']))
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->with(
                'ERR.invalidThemeTemplatePath',
                ['<bad-path>', '._'],
                'contao_default',
            )
            ->willReturn('<message>')
        ;

        $listener = $this->getListener(null, $theme, $translator);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('<message>');

        $listener('<bad-path>');
    }

    private function getListener(ContaoFilesystemLoaderWarmer $filesystemLoaderWarmer = null, Theme $theme = null, TranslatorInterface $translator = null): ThemeTemplatesListener
    {
        return new ThemeTemplatesListener(
            $filesystemLoaderWarmer ?? $this->createMock(ContaoFilesystemLoaderWarmer::class),
            $theme ?? $this->createMock(Theme::class),
            $translator ?? $this->createMock(TranslatorInterface::class)
        );
    }
}
