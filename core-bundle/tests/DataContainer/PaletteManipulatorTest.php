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

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DataContainer\PaletteNotFoundException;
use Contao\CoreBundle\DataContainer\PalettePositionException;
use Contao\CoreBundle\Tests\TestCase;

class PaletteManipulatorTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testPrependsAFieldToAPalette(): void
    {
        $pm = PaletteManipulator::create()
            ->addField('foo', 'config_legend', 'prepend', 'config_legend')
        ;

        $this->assertSame(
            '{config_legend},foo,bar',
            $pm->applyToString('{config_legend},bar'),
        );

        $this->assertSame(
            '{config_legend},foo,bar;{foo_legend},baz',
            $pm->applyToString('{config_legend},bar;{foo_legend},baz'),
        );

        $this->assertSame(
            '{foo_legend},baz;{config_legend},foo',
            $pm->applyToString('{foo_legend},baz'),
        );
    }

    public function testAppendsAFieldToAPalette(): void
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', 'config_legend', 'append', 'config_legend')
        ;

        $this->assertSame(
            '{config_legend},foo,bar',
            $pm->applyToString('{config_legend},foo'),
        );

        $this->assertSame(
            '{config_legend},foo,bar;{foo_legend},baz',
            $pm->applyToString('{config_legend},foo;{foo_legend},baz'),
        );

        $this->assertSame(
            '{foo_legend},baz;{config_legend},bar',
            $pm->applyToString('{foo_legend},baz'),
        );
    }

    public function testAddsALegendBeforeAnotherLegend(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('config_legend', 'foo_legend', 'before')
            ->addField('foo', 'config_legend', 'append')
        ;

        $this->assertSame(
            '{config_legend},foo;{foo_legend},baz',
            $pm->applyToString('{foo_legend},baz'),
        );

        $this->assertSame(
            '{bar_legend},baz;{config_legend},foo',
            $pm->applyToString('{bar_legend},baz'),
        );
    }

    public function testAddsALegendAfterAnotherLegend(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('config_legend', 'foo_legend', 'after')
            ->addField('foo', 'config_legend')
        ;

        $this->assertSame(
            '{foo_legend},baz;{config_legend},foo',
            $pm->applyToString('{foo_legend},baz'),
        );

        $this->assertSame(
            '{bar_legend},baz;{config_legend},foo',
            $pm->applyToString('{bar_legend},baz'),
        );
    }

    public function testAddsAFieldBeforeAnotherField(): void
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', 'foo', 'before')
        ;

        $this->assertSame(
            '{config_legend},bar,foo',
            $pm->applyToString('{config_legend},foo'),
        );

        $this->assertSame(
            '{config_legend},baz,bar',
            $pm->applyToString('{config_legend},baz'),
        );
    }

    public function testAddsAFieldAfterAnotherField(): void
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', 'foo', 'after')
        ;

        $this->assertSame(
            '{config_legend},foo,bar',
            $pm->applyToString('{config_legend},foo'),
        );

        $this->assertSame(
            '{config_legend},baz,bar',
            $pm->applyToString('{config_legend},baz'),
        );
    }

    public function testSkipsTheLegendsIfConfigured(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('foobar_legend', '', 'append')
            ->addField('field3', 'field1')
            ->addField('field4', 'field3')
            ->addField('field2', 'field1')
            ->addField('foobar', 'foobar_legend', 'append')
            ->addField('first', '', 'prepend')
        ;

        $this->assertSame(
            'first,field1,field2,field3,field4,foobar',
            $pm->applyToString('field1', true),
        );
    }

    public function testAddsAFieldToMultipleParents(): void
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', ['baz', 'foo'])
        ;

        $this->assertSame(
            '{foobar_legend},foo,bar',
            $pm->applyToString('{foobar_legend},foo'),
        );
    }

    public function testAddsAFieldToAnEmptyPalette(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('name_legend', '', 'prepend')
            ->addField('name', 'name_legend', 'append')
        ;

        $this->assertSame('{name_legend},name', $pm->applyToString(''));

        $pm = PaletteManipulator::create()
            ->addField('name', 'name_legend', 'append')
        ;

        $this->assertSame('name', $pm->applyToString(''));

        $pm = PaletteManipulator::create()
            ->addField('name', 'name_legend', 'append', 'name_legend')
        ;

        $this->assertSame('{name_legend},name', $pm->applyToString(''));
    }

    public function testAddsAFieldToANamelessLegend(): void
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', 'foo', 'after')
        ;

        $this->assertSame(
            '{name_legend},name;foo,bar',
            $pm->applyToString('{name_legend},name;foo'),
        );
    }

    public function testIgnoresEmptyLegends(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('empty_legend', '', 'append')
            ->addField('foo', 'bar', 'before')
        ;

        $this->assertSame(
            '{foobar_legend},foo,bar',
            $pm->applyToString('{foobar_legend},bar'),
        );
    }

    public function testIgnoresEmptyGroups(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('foo_legend', '')
            ->addField('foo', 'foo_legend')
        ;

        $this->assertSame(
            '{bar_legend},bar;{foo_legend},foo',
            $pm->applyToString('{bar_legend},bar;;'),
        );
    }

    public function testIgnoresDuplicateLegends(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('foobar_legend', '', 'append')
            ->addField('bar', 'foo', 'after')
        ;

        $this->assertSame(
            '{foobar_legend},foo,bar;{other_legend},other',
            $pm->applyToString('{foobar_legend},foo;{other_legend},other'),
        );
    }

    public function testAddsHiddenLegends(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('foobar_legend', '', 'append', true)
            ->addField(['foo', 'bar'], 'foobar_legend', 'append')
        ;

        $this->assertSame(
            '{name_legend},name;{foobar_legend:hide},foo,bar',
            $pm->applyToString('{name_legend},name'),
        );
    }

    public function testAppliesChangesToADcaPalette(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('foobar_legend', '', 'append')
            ->addField(['foo', 'bar'], 'foobar_legend', 'append')
        ;

        $GLOBALS['TL_DCA']['tl_test']['palettes']['default'] = '{name_legend},name';

        $pm->applyToPalette('default', 'tl_test');

        $this->assertSame(
            '{name_legend},name;{foobar_legend},foo,bar',
            $GLOBALS['TL_DCA']['tl_test']['palettes']['default'],
        );
    }

    public function testAppliesChangesToADcaSubpalette(): void
    {
        $pm = PaletteManipulator::create()
            ->addField(['foo', 'bar'], 'lastname')
        ;

        $GLOBALS['TL_DCA']['tl_test']['subpalettes']['name'] = 'firstname,lastname';

        $pm->applyToSubpalette('name', 'tl_test');

        $this->assertSame(
            'firstname,lastname,foo,bar',
            $GLOBALS['TL_DCA']['tl_test']['subpalettes']['name'],
        );
    }

    public function testAddsAFieldToTheFallbackPalette(): void
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', 'foo', 'after', 'name_legend')
        ;

        $this->assertSame('{name_legend},name,bar', $pm->applyToString('{name_legend},name'));
    }

    public function testCallsTheFallbackClosure(): void
    {
        $closureCalled = false;

        $pm = PaletteManipulator::create()
            ->addField(
                'bar',
                'foo',
                'after',
                function (array $config, array $action, bool $skipLegends) use (&$closureCalled): void {
                    $closureCalled = true;

                    $this->assertIsArray($config);
                    $this->assertIsArray($action);
                    $this->assertIsBool($skipLegends);

                    $this->assertArrayHasKey('fields', $action);
                    $this->assertArrayHasKey('parents', $action);
                    $this->assertArrayHasKey('position', $action);
                },
            )
        ;

        $pm->applyToString('baz');

        $this->assertTrue($closureCalled);
    }

    public function testFailsIfTheDcaPaletteDoesNotExist(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('foobar_legend', '', 'append')
            ->addField(['foo', 'bar'], 'foobar_legend', 'append')
        ;

        // Make sure the palette is not here (for whatever reason another test might have set it)
        unset($GLOBALS['TL_DCA']['tl_test']['palettes']['default']);

        $this->expectException(PaletteNotFoundException::class);

        $pm->applyToPalette('default', 'tl_test');
    }

    public function testFailsIfTheDcaSubpaletteDoesNotExist(): void
    {
        $pm = PaletteManipulator::create()
            ->addField(['foo', 'bar'], 'lastname')
        ;

        // Make sure the palette is not here (for whatever reason another test might have set it)
        unset($GLOBALS['TL_DCA']['tl_test']['subpalettes']['name']);

        $this->expectException(PaletteNotFoundException::class);

        $pm->applyToSubpalette('name', 'tl_test');
    }

    public function testFailsIfThePositionIsInvalid(): void
    {
        $this->expectException(PalettePositionException::class);

        PaletteManipulator::create()
            ->addField('bar', 'foo', 'foo_position')
            ->applyToString('foo')
        ;
    }

    public function testFailsIfTheFallbackPositionIsInvalid(): void
    {
        $this->expectException(PalettePositionException::class);

        PaletteManipulator::create()
            ->addField('bar', 'foo', 'after', 'foobar_legend', 'after')
            ->applyToString('foo')
        ;
    }

    public function testRemovesAnExistingFieldFromAPalette(): void
    {
        $pm = PaletteManipulator::create()->removeField('foo');

        $this->assertSame(
            '{foo_legend},baz;{config_legend},test',
            $pm->applyToString('{foo_legend},baz;{config_legend},foo,test'),
        );

        $this->assertSame(
            '{foo_legend},baz',
            $pm->applyToString('{foo_legend},baz;{config_legend},foo'),
        );

        $this->assertSame(
            '{foo_legend},baz',
            $pm->applyToString('{foo_legend},foo,baz;{config_legend},foo'),
        );
    }

    public function testRemovesMultipleFieldsFromAPalette(): void
    {
        $pm = PaletteManipulator::create()->removeField(['foo', 'baz']);

        $this->assertSame('', $pm->applyToString('{foo_legend},baz;{config_legend},foo'));
        $this->assertSame('{foo_legend},bar', $pm->applyToString('{foo_legend},bar,baz;{config_legend},foo'));
    }

    public function testRemovesAnExistingFieldFromALegend(): void
    {
        $pm = PaletteManipulator::create()->removeField('foo', 'config_legend');

        $this->assertSame(
            '{foo_legend},foo,baz',
            $pm->applyToString('{foo_legend},foo,baz;{config_legend},foo'),
        );
    }

    public function testAppliesTheRemovalToAPalette(): void
    {
        $GLOBALS['TL_DCA']['tl_test']['palettes']['name'] = '{foo_legend},firstname,lastname';

        $pm = PaletteManipulator::create()->removeField(['firstname']);
        $pm->applyToPalette('name', 'tl_test');

        $this->assertSame(
            '{foo_legend},lastname',
            $GLOBALS['TL_DCA']['tl_test']['palettes']['name'],
        );
    }

    public function testAppliesTheRemovalToASubpalette(): void
    {
        $GLOBALS['TL_DCA']['tl_test']['subpalettes']['name'] = 'firstname,lastname';

        $pm = PaletteManipulator::create()->removeField(['firstname']);
        $pm->applyToSubpalette('name', 'tl_test');

        $this->assertSame('lastname', $GLOBALS['TL_DCA']['tl_test']['subpalettes']['name']);
    }

    public function testRemovesFieldsBeforeAddingFields(): void
    {
        $pm = PaletteManipulator::create()
            ->addField('title', 'firstname', PaletteManipulator::POSITION_BEFORE)
            ->removeField('firstname')
        ;

        $this->assertSame(
            '{contact_legend},title,lastname',
            $pm->applyToString('{contact_legend},firstname,lastname'),
        );
    }

    public function testAddsLegendToEndIfParentDoesNotExist(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('config_legend', 'notexist_legend')
            ->addField('foo', 'config_legend', 'append')
        ;

        $this->assertSame(
            '{foo_legend},baz;{config_legend},foo',
            $pm->applyToString('{foo_legend},baz'),
        );
    }

    public function testAddsLegendToEndIfParentIsNull(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('config_legend')
            ->addField('foo', 'config_legend', 'append')
        ;

        $this->assertSame(
            '{foo_legend},baz;{config_legend},foo',
            $pm->applyToString('{foo_legend},baz'),
        );
    }

    public function testAddsFieldToEndIfParentDoesNotExist(): void
    {
        $pm = PaletteManipulator::create()
            ->addField('foo', 'notexist')
        ;

        $this->assertSame(
            '{config_legend},baz,foo',
            $pm->applyToString('{config_legend},baz'),
        );
    }

    public function testAddsFieldToEndIfParentIsNull(): void
    {
        $pm = PaletteManipulator::create()
            ->addField('foo')
        ;

        $this->assertSame(
            '{config_legend},baz,foo',
            $pm->applyToString('{config_legend},baz'),
        );
    }
}
