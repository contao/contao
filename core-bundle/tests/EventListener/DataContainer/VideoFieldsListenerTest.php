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

use Contao\CoreBundle\EventListener\DataContainer\VideoFieldsListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Symfony\Component\Mime\MimeTypesInterface;

class VideoFieldsListenerTest extends TestCase
{
    private const PALETTE = 'name,protected,syncExclude;meta';

    public function testAddsTheVideoFieldsToThePalette(): void
    {
        $dc = $this->createClassWithPropertiesStub(DataContainer::class, ['id' => 'foo.mp4']);

        $mimeTypes = $this->createMock(MimeTypesInterface::class);
        $mimeTypes
            ->expects($this->once())
            ->method('guessMimeType')
            ->willReturn('video/')
        ;

        $this->assertSame(
            'name,videoSizes,protected,syncExclude;meta',
            (new VideoFieldsListener($mimeTypes))->addVideoFields(self::PALETTE, $dc),
        );
    }

    public function testDoesNotAddTheVideoFieldsToThePalette(): void
    {
        $dc = $this->createClassWithPropertiesStub(DataContainer::class, ['id' => 'foo.txt']);

        $mimeTypes = $this->createMock(MimeTypesInterface::class);
        $mimeTypes
            ->expects($this->once())
            ->method('guessMimeType')
            ->willReturn('text/')
        ;

        $this->assertSame(self::PALETTE, (new VideoFieldsListener($mimeTypes))->addVideoFields(self::PALETTE, $dc));
    }
}
