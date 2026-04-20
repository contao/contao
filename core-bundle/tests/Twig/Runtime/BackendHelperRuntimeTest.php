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
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME']);

        parent::tearDown();
    }

    public function testDelegatesCallsForIcon(): void
    {
        $attributes = new HtmlAttributes()->set('foo', 'bar');

        $imageAdapter = $this->createAdapterMock(['getHtml']);
        $imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('icon.svg', 'alt', $attributes)
            ->willReturn('icon HTML')
        ;

        $framework = $this->createContaoFrameworkStub([Image::class => $imageAdapter]);

        $this->assertSame('icon HTML', new BackendHelperRuntime($framework)->icon(
            'icon.svg', 'alt', $attributes,
        ));
    }

    public function testReturnsDefaultIconForNoMimeType(): void
    {
        $attributes = new HtmlAttributes()->set('foo', 'bar');

        $imageAdapter = $this->createAdapterMock(['getHtml']);
        $imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('plain.svg', 'alt', $attributes)
            ->willReturn('icon HTML')
        ;

        $framework = $this->createContaoFrameworkStub([Image::class => $imageAdapter]);

        $this->assertSame('icon HTML', new BackendHelperRuntime($framework)->fileIcon(
            $this->createStub(FilesystemItem::class), 'alt', $attributes,
        ));
    }

    public function testReturnsIconForMimeType(): void
    {
        $attributes = new HtmlAttributes()->set('foo', 'bar');

        $imageAdapter = $this->createAdapterMock(['getHtml']);
        $imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('image.svg', 'alt', $attributes)
            ->willReturn('icon HTML')
        ;

        $framework = $this->createContaoFrameworkStub([Image::class => $imageAdapter]);

        $fileitem = $this->createMock(FilesystemItem::class);
        $fileitem
            ->expects($this->once())
            ->method('getMimeType')
            ->willReturn('image/jpeg')
        ;

        $GLOBALS['TL_MIME'] = ['jpg' => ['image/jpeg', 'image.svg']];

        $this->assertSame('icon HTML', new BackendHelperRuntime($framework)->fileIcon(
            $fileitem, 'alt', $attributes,
        ));
    }

    public function testReturnsDefaultIconForMissingRegisteredMimeType(): void
    {
        $attributes = new HtmlAttributes()->set('foo', 'bar');

        $imageAdapter = $this->createAdapterMock(['getHtml']);
        $imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('plain.svg', 'alt', $attributes)
            ->willReturn('icon HTML')
        ;

        $framework = $this->createContaoFrameworkStub([Image::class => $imageAdapter]);

        $fileitem = $this->createMock(FilesystemItem::class);
        $fileitem
            ->expects($this->once())
            ->method('getMimeType')
            ->willReturn('image/jpeg')
        ;

        $this->assertSame('icon HTML', new BackendHelperRuntime($framework)->fileIcon(
            $fileitem, 'alt', $attributes,
        ));
    }
}
