<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Runtime;

use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\BackendHelperRuntime;
use Contao\Image;

class BackendHelperRuntimeTest extends TestCase
{
    public function testDelegatesCallsForIcon(): void
    {
        $imageAdapter = $this->mockAdapter(['getHtml']);
        $imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('icon.svg', 'alt', 'foo="bar"')
            ->willReturn('icon HTML')
        ;

        $framework = $this->mockContaoFramework([Image::class => $imageAdapter]);

        $this->assertSame('icon HTML', (new BackendHelperRuntime($framework))->icon(
            'icon.svg', 'alt', (new HtmlAttributes())->set('foo', 'bar'),
        ));
    }

    public function testReturnsDefaultIconForNoMimeType(): void
    {
        $imageAdapter = $this->mockAdapter(['getHtml']);
        $imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('regular.svg', 'alt', 'foo="bar"')
            ->willReturn('icon HTML')
        ;

        $framework = $this->mockContaoFramework([Image::class => $imageAdapter]);

        $this->assertSame('icon HTML', (new BackendHelperRuntime($framework))->file_icon(
            $this->createMock(FilesystemItem::class), 'alt', (new HtmlAttributes())->set('foo', 'bar'),
        ));
    }

    public function testReturnsIconForMimeType(): void
    {
        $imageAdapter = $this->mockAdapter(['getHtml']);
        $imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('image.svg', 'alt', 'foo="bar"')
            ->willReturn('icon HTML')
        ;

        $framework = $this->mockContaoFramework([Image::class => $imageAdapter]);

        $fileitem = $this->createMock(FilesystemItem::class);
        $fileitem
            ->expects($this->once())
            ->method('getMimeType')
            ->willReturn('image/jpeg')
        ;

        $GLOBALS['TL_MIME'] = ['jpg' => ['image/jpeg', 'image.svg']];

        $this->assertSame('icon HTML', (new BackendHelperRuntime($framework))->file_icon(
            $fileitem, 'alt', (new HtmlAttributes())->set('foo', 'bar'),
        ));

        unset($GLOBALS['TL_MIME']);
    }

    public function testReturnsDefaultIconForMissingRegisteredMimeType(): void
    {
        $imageAdapter = $this->mockAdapter(['getHtml']);
        $imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('regular.svg', 'alt', 'foo="bar"')
            ->willReturn('icon HTML')
        ;

        $framework = $this->mockContaoFramework([Image::class => $imageAdapter]);

        $fileitem = $this->createMock(FilesystemItem::class);
        $fileitem
            ->expects($this->once())
            ->method('getMimeType')
            ->willReturn('image/jpeg')
        ;

        $this->assertSame('icon HTML', (new BackendHelperRuntime($framework))->file_icon(
            $fileitem, 'alt', (new HtmlAttributes())->set('foo', 'bar'),
        ));
    }
}
