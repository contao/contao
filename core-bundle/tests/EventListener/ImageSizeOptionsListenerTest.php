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

use Contao\BackendUser;
use Contao\CoreBundle\EventListener\ImageSizeOptionsListener;
use Contao\CoreBundle\Image\ImageSizes;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Security;

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

        $backendUser = $this->mockClassWithProperties(BackendUser::class, [
            'isAdmin' => true,
        ]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($backendUser)
        ;

        $imageSizes = $this->createMock(ImageSizes::class);
        $imageSizes
            ->expects($this->once())
            ->method('getOptionsForUser')
            ->with($backendUser)
            ->willReturn($imageSizeConfig)
        ;

        $listener = new ImageSizeOptionsListener($security, $imageSizes);

        $this->assertSame($imageSizeConfig, $listener());
    }
}
