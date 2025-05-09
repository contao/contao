<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DataContainer;

use Contao\CoreBundle\DataContainer\Palette;
use Contao\CoreBundle\Tests\TestCase;

class PaletteTest extends TestCase
{
    public function testPaletteHandling(): void
    {
        $paletteString = '{config_legend},foo,bar;{custom_legend},baz';
        $palette = Palette::fromString($paletteString);

        $this->assertTrue($palette->hasLegend('config_legend'));
        $this->assertTrue($palette->hasLegend('custom_legend'));
        $this->assertFalse($palette->hasLegend('nonexistent_legend'));

        $this->assertTrue($palette->hasField('foo'));
        $this->assertTrue($palette->hasField('bar', 'config_legend'));
        $this->assertTrue($palette->hasField('baz', 'custom_legend'));
        $this->assertFalse($palette->hasField('baz', 'config_legend'));
        $this->assertFalse($palette->hasField('qux'));

        $this->assertSame($paletteString, (string) $palette);
        $this->assertSame($paletteString, $palette->toString());
    }
}
