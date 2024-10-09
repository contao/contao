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

use Contao\CoreBundle\EventListener\DataContainer\SubtitleFieldListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;

class SubtitleFieldListenerTest extends TestCase
{
    private const PALETTE = 'name,protected,syncExclude;meta';

    public function testAddsTheSubtitleFieldsToThePalette(): void
    {
        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 'foo.vtt']);

        $this->assertSame(
            'name,subtitlesLanguage,subtitlesType,protected,syncExclude;meta',
            (new SubtitleFieldListener())(self::PALETTE, $dc),
        );
    }

    public function testDoesNotAddTheSubtitleFieldsToThePalette(): void
    {
        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 'bar.baz']);

        $this->assertSame(
            self::PALETTE,
            (new SubtitleFieldListener())(self::PALETTE, $dc),
        );
    }
}
