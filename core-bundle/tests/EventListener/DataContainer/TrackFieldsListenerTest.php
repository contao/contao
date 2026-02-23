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

use Contao\CoreBundle\EventListener\DataContainer\TrackFieldsListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;

class TrackFieldsListenerTest extends TestCase
{
    private const PALETTE = 'name,protected,syncExclude;meta';

    public function testAddsTheTextTrackFieldsToThePalette(): void
    {
        $dc = $this->createClassWithPropertiesStub(DataContainer::class, ['id' => 'foo.vtt']);

        $this->assertSame(
            'name,textTrackLanguage,textTrackType,protected,syncExclude;meta',
            (new TrackFieldsListener())->addTextTrackFields(self::PALETTE, $dc),
        );
    }

    public function testDoesNotAddTheTextTrackFieldsToThePalette(): void
    {
        $dc = $this->createClassWithPropertiesStub(DataContainer::class, ['id' => 'bar.baz']);

        $this->assertSame(self::PALETTE, (new TrackFieldsListener())->addTextTrackFields(self::PALETTE, $dc));
    }
}
