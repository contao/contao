<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\ImageSizeOptionsListener;
use Contao\CoreBundle\Image\ImageSizes;
use Contao\CoreBundle\Tests\TestCase;

class ImageSizeOptionsListenerTest extends TestCase
{
    public function testGetImageSizesForUser(): void
    {
        $imageSizeConfig = [
            'image_sizes' => [],
            'custom' => [
                'crop', 'proportional', 'box',
            ],
        ];

        $imageSizes = $this->createMock(ImageSizes::class);
        $imageSizes
            ->expects($this->once())
            ->method('getOptionsForUser')
            ->willReturn($imageSizeConfig)
        ;

        $listener = new ImageSizeOptionsListener($imageSizes);

        $this->assertSame($imageSizeConfig, $listener());
    }
}
