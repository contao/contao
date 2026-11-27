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
use Contao\CoreBundle\DataContainer\PalettePositionException;
use Contao\CoreBundle\Tests\TestCase;

class PaletteTest extends TestCase
{
    public function testPaletteHandling(): void
    {
        $paletteString = '{config_legend},foo,bar;{custom_legend},baz';
        $palette = new Palette($paletteString);

        $this->assertTrue($palette->hasLegend('config_legend'));
        $this->assertTrue($palette->hasLegend('custom_legend'));
        $this->assertFalse($palette->hasLegend('nonexistent_legend'));

        $this->assertTrue($palette->hasField('foo'));
        $this->assertTrue($palette->hasField('bar', 'config_legend'));
        $this->assertTrue($palette->hasField('baz', 'custom_legend'));
        $this->assertFalse($palette->hasField('baz', 'config_legend'));
        $this->assertFalse($palette->hasField('qux'));

        $this->assertSame('config_legend', $palette->getLegendForField('foo'));
        $this->assertSame('custom_legend', $palette->getLegendForField('baz'));
        $this->assertNull($palette->getLegendForField('nonexistent_field'));

        $this->assertSame($paletteString, (string) $palette);
        $this->assertSame($paletteString, $palette->toString());
    }

    public function testEmptyPaletteInitialization(): void
    {
        $palette = new Palette('');
        $this->assertSame('', $palette->toString());
        $this->assertSame('', (string) $palette);
    }

    public function testAddLegendAppend(): void
    {
        $palette = new Palette('{first_legend},field1');
        $palette->addLegend('second_legend', null, Palette::POSITION_APPEND, true);
        $palette->addField('field2', 'second_legend', Palette::POSITION_APPEND);

        $this->assertSame('{first_legend},field1;{second_legend:hide},field2', $palette->toString());
    }

    public function testAddLegendPrepend(): void
    {
        $palette = new Palette('{first_legend},field1');
        $palette->addLegend('second_legend', null, Palette::POSITION_PREPEND);
        $palette->addField('field2', 'second_legend', Palette::POSITION_APPEND);

        $this->assertSame('{second_legend},field2;{first_legend},field1', $palette->toString());
    }

    public function testAddLegendAfterWithoutParent(): void
    {
        $palette = new Palette('{first_legend},field1');
        $palette->addLegend('second_legend', null, Palette::POSITION_AFTER, true);
        $palette->addField('field2', 'second_legend', Palette::POSITION_APPEND);

        $this->assertSame('{first_legend},field1;{second_legend:hide},field2', $palette->toString());
    }

    public function testAddLegendAfter(): void
    {
        $palette = new Palette('{first_legend},field1;{second_legend:hide},field2');
        $palette->addLegend('third_legend', 'first_legend', Palette::POSITION_AFTER, true);
        $palette->addField('field3', 'third_legend', Palette::POSITION_APPEND);

        $this->assertSame('{first_legend},field1;{third_legend:hide},field3;{second_legend:hide},field2', $palette->toString());
    }

    public function testAddFieldToExistingLegend(): void
    {
        $palette = new Palette('{legend},field1');
        $palette->addField('field2', 'legend', Palette::POSITION_APPEND);

        $this->assertTrue($palette->hasField('field2'));
    }

    public function testAddFieldToFieldAfter(): void
    {
        $palette = new Palette('{legend},field1,field2');
        $palette->addField('field3', 'field1');

        $this->assertSame('{legend},field1,field3,field2', $palette->toString());
    }

    public function testAddFieldWithFallback(): void
    {
        $palette = new Palette();
        $palette->addField('fallbackField', null, Palette::POSITION_APPEND, ['fallbackLegend']);

        $this->assertTrue($palette->hasField('fallbackField'));
        $this->assertTrue($palette->hasLegend('fallbackLegend'));
    }

    public function testRemoveField(): void
    {
        $palette = new Palette('{legend},field1,field2');
        $palette->removeField('field1');

        $this->assertFalse($palette->hasField('field1'));
        $this->assertTrue($palette->hasField('field2'));
    }

    public function testRemoveExpandedSelector(): void
    {
        $palette = new Palette('{legend},field1,field2,[field2],field3,[field3],field4,[EOF],field5,[EOF],field6,[field6],field7,[EOF],field8');
        $palette->removeField('field2');

        $this->assertSame('{legend},field1,field6,[field6],field7,[EOF],field8', $palette->toString());
    }

    public function testRemoveNestedSelector(): void
    {
        $palette = new Palette('{legend},field1,field2,[field2],field3,[field3],field4,[EOF],field5,[EOF],field6,[field6],field7,[EOF],field8');
        $palette->removeField('field3');

        $this->assertSame('{legend},field1,field2,[field2],field5,[EOF],field6,[field6],field7,[EOF],field8', $palette->toString());
    }

    public function testRemoveCollapsedSelector(): void
    {
        $palette = new Palette('field1,field2,[field2],field3,[EOF]');
        $palette->removeField('field1');

        $this->assertSame('field2,[field2],field3,[EOF]', $palette->toString());
    }

    public function testRemoveMultipleFieldsFromSpecifiedLegend(): void
    {
        $palette = new Palette('{first_legend},field1,field2,[field2],field3,[EOF],field4;{second_legend},field2,[field2],field3,[EOF]');
        $palette->removeField(['field1', 'field2'], 'first_legend');

        $this->assertSame('{first_legend},field4;{second_legend},field2,[field2],field3,[EOF]', $palette->toString());
    }

    public function testRemoveChildField(): void
    {
        $palette = new Palette('field1,[field1],field2,field3,[EOF]');
        $palette->removeField('field2');

        $this->assertSame('field1,[field1],field3,[EOF]', $palette->toString());
    }

    public function testInvalidPositionThrowsException(): void
    {
        $this->expectException(PalettePositionException::class);

        $palette = new Palette();
        $palette->addField('field', 'legend', 'invalid-position');
    }

    public function testInvalidFallbackPositionThrowsException(): void
    {
        $this->expectException(PalettePositionException::class);

        $palette = new Palette();
        $palette->addField('field', 'legend', Palette::POSITION_BEFORE, null, Palette::POSITION_BEFORE);
    }
}
