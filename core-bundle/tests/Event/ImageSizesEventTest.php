<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Event;

use Contao\BackendUser;
use Contao\CoreBundle\Event\ImageSizesEvent;
use PHPUnit\Framework\TestCase;

class ImageSizesEventTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $event = new ImageSizesEvent([1]);

        $this->assertInstanceOf('Contao\CoreBundle\Event\ImageSizesEvent', $event);
    }

    public function testSupportsReadingAndWritingImageSizes(): void
    {
        $event = new ImageSizesEvent([1]);

        $this->assertSame([1], $event->getImageSizes());

        $event->setImageSizes([1, 2]);

        $this->assertSame([1, 2], $event->getImageSizes());
    }

    public function testSupportsReadingTheUserObject(): void
    {
        $user = $this->createMock(BackendUser::class);
        $event = new ImageSizesEvent([1], $user);

        $this->assertSame($user, $event->getUser());
    }
}
