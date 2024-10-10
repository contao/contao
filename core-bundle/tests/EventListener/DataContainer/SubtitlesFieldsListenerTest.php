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

use Contao\CoreBundle\EventListener\DataContainer\SubtitlesFieldsListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;

class SubtitlesFieldsListenerTest extends TestCase
{
    private const PALETTE = 'name,protected,syncExclude;meta';

    public function testAddsTheSubtitlesFieldsToThePalette(): void
    {
        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 'foo.vtt']);

        $this->assertSame(
            'name,subtitlesLanguage,subtitlesType,protected,syncExclude;meta',
            (new SubtitlesFieldsListener)->addSubtitlesFields(self::PALETTE, $dc),
        );
    }

    public function testDoesNotAddTheSubtitlesFieldsToThePalette(): void
    {
        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 'bar.baz']);

        $this->assertSame(self::PALETTE, (new SubtitlesFieldsListener)->addSubtitlesFields(self::PALETTE, $dc));
    }
}
