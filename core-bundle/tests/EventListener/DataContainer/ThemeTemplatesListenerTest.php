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
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
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
        $themeNamespace = $this->createMock(ThemeNamespace::class);
        $themeNamespace
            ->method('generateSlug')
            ->with('<bad-path>')
            ->willThrowException(new InvalidThemePathException('<bad-path>', ['.', '_']))
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->with('ERR.invalidThemeTemplatePath', ['<bad-path>', '._'], 'contao_default')
            ->willReturn('<message>')
        ;

        $listener = $this->getListener(null, $themeNamespace, $translator);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('<message>');

        $listener('<bad-path>');
    }

    private function getListener(ContaoFilesystemLoaderWarmer|null $filesystemLoaderWarmer = null, ThemeNamespace|null $themeNamespace = null, TranslatorInterface|null $translator = null): ThemeTemplatesListener
    {
        return new ThemeTemplatesListener(
            $filesystemLoaderWarmer ?? $this->createMock(ContaoFilesystemLoaderWarmer::class),
            $themeNamespace ?? $this->createMock(ThemeNamespace::class),
            $translator ?? $this->createMock(TranslatorInterface::class),
        );
    }
}
