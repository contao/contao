<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Event;

use Contao\BackendUser;
use Contao\CoreBundle\Event\ImageSizesEvent;
use PHPUnit\Framework\TestCase;

class ImageSizesEventTest extends TestCase
{
    public function testSupportsReadingAndWritingImageSizes(): void
    {
        $event = new ImageSizesEvent([]);
        $this->assertSame([], $event->getImageSizes());

        $event->setImageSizes(['foo' => ['bar', 'baz']]);
        $this->assertSame(['foo' => ['bar', 'baz']], $event->getImageSizes());

        $event->setImageSizes(['foo' => ['bar', 'baz'], 'baz' => ['foo', 'bar']]);
        $this->assertSame(['foo' => ['bar', 'baz'], 'baz' => ['foo', 'bar']], $event->getImageSizes());
    }

    public function testSupportsReadingTheUserObject(): void
    {
        $user = $this->createStub(BackendUser::class);
        $event = new ImageSizesEvent([1], $user);

        $this->assertSame($user, $event->getUser());
    }
}
