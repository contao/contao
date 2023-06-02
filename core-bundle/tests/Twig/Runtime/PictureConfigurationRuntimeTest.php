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

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\PictureConfigurationRuntime;

class PictureConfigurationRuntimeTest extends TestCase
{
    public function testGeneratesEmptyPictureConfiguration(): void
    {
        $runtime = new PictureConfigurationRuntime();
        $configuration = $runtime->fromArray([]);

        $this->assertSame('', $configuration->getSize()->getDensities());
        $this->assertSame('', $configuration->getSize()->getSizes());
        $this->assertSame(0, $configuration->getSize()->getResizeConfig()->getWidth());
        $this->assertSame(0, $configuration->getSize()->getResizeConfig()->getHeight());
        $this->assertSame(0, $configuration->getSize()->getResizeConfig()->getZoomLevel());
        $this->assertSame('crop', $configuration->getSize()->getResizeConfig()->getMode());
        $this->assertSame(['.default' => ['.default']], $configuration->getFormats());
        $this->assertSame([], $configuration->getSizeItems());
    }

    public function testGeneratesPictureConfigurationWithoutItems(): void
    {
        $runtime = new PictureConfigurationRuntime();

        $configuration = $runtime->fromArray([
            'densities' => '1x, 2x',
            'sizes' => '100vw',
            'width' => '200',
            'height' => '100',
            'zoom' => '75',
            'resizeMode' => 'box',
            'formats' => ['jpg' => ['jpg', 'webp']],
        ]);

        $this->assertSame('1x, 2x', $configuration->getSize()->getDensities());
        $this->assertSame('100vw', $configuration->getSize()->getSizes());
        $this->assertSame(200, $configuration->getSize()->getResizeConfig()->getWidth());
        $this->assertSame(100, $configuration->getSize()->getResizeConfig()->getHeight());
        $this->assertSame(75, $configuration->getSize()->getResizeConfig()->getZoomLevel());
        $this->assertSame('box', $configuration->getSize()->getResizeConfig()->getMode());
        $this->assertSame(['jpg' => ['jpg', 'webp'], '.default' => ['.default']], $configuration->getFormats());
        $this->assertSame([], $configuration->getSizeItems());
    }

    public function testGeneratesPictureConfigurationWithItems(): void
    {
        $runtime = new PictureConfigurationRuntime();

        $configuration = $runtime->fromArray([
            'items' => [
                [
                ],
                [
                    'densities' => '1x, 2x',
                    'sizes' => '100vw',
                    'width' => '200',
                    'height' => '100',
                    'zoom' => '75',
                    'resizeMode' => 'box',
                    'media' => '(max-width: 640px)',
                ],
            ],
        ]);

        $this->assertCount(2, $configuration->getSizeItems());

        [$item1, $item2] = $configuration->getSizeItems();

        $this->assertSame('', $item1->getDensities());
        $this->assertSame('', $item1->getSizes());
        $this->assertSame(0, $item1->getResizeConfig()->getWidth());
        $this->assertSame(0, $item1->getResizeConfig()->getHeight());
        $this->assertSame(0, $item1->getResizeConfig()->getZoomLevel());
        $this->assertSame('crop', $item1->getResizeConfig()->getMode());
        $this->assertSame('', $item1->getMedia());

        $this->assertSame('1x, 2x', $item2->getDensities());
        $this->assertSame('100vw', $item2->getSizes());
        $this->assertSame(200, $item2->getResizeConfig()->getWidth());
        $this->assertSame(100, $item2->getResizeConfig()->getHeight());
        $this->assertSame(75, $item2->getResizeConfig()->getZoomLevel());
        $this->assertSame('box', $item2->getResizeConfig()->getMode());
        $this->assertSame('(max-width: 640px)', $item2->getMedia());
    }

    public function testFailsWithInvalidConfiguration(): void
    {
        $runtime = new PictureConfigurationRuntime();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not map picture configuration key(s) "foo", "bar".');

        $runtime->fromArray([
            'foo' => 'value',
            'bar' => 'value',
            'sizes' => '100vw',
        ]);
    }

    public function testFailsWithInvalidItemConfiguration(): void
    {
        $runtime = new PictureConfigurationRuntime();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not map picture configuration key(s) "items.foo", "items.bar".');

        $runtime->fromArray([
            'items' => [
                [
                    'width' => '100',
                    'foo' => 'value',
                    'bar' => 'value',
                ],
            ],
        ]);
    }
}
