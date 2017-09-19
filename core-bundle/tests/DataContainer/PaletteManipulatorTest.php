<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\Tests\TestCase;

/**
 * Tests the PaletteManipulator class.
 */
class PaletteManipulatorTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated(): void
    {
        $pm = PaletteManipulator::create();

        $this->assertInstanceOf('Contao\CoreBundle\DataContainer\PaletteManipulator', $pm);
    }

    /**
     * Tests prepending a field.
     */
    public function testPrependsAFieldToAPalette(): void
    {
        $pm = PaletteManipulator::create()
            ->addField('foo', 'config_legend', PaletteManipulator::POSITION_PREPEND, 'config_legend')
        ;

        $this->assertSame(
            '{config_legend},foo,bar',
            $pm->applyToString('{config_legend},bar')
        );

        $this->assertSame(
            '{config_legend},foo,bar;{foo_legend},baz',
            $pm->applyToString('{config_legend},bar;{foo_legend},baz')
        );

        $this->assertSame(
            '{foo_legend},baz;{config_legend},foo',
            $pm->applyToString('{foo_legend},baz')
        );
    }

    /**
     * Tests appending a field.
     */
    public function testAppendsAFieldToAPalette(): void
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', 'config_legend', PaletteManipulator::POSITION_APPEND, 'config_legend')
        ;

        $this->assertSame(
            '{config_legend},foo,bar',
            $pm->applyToString('{config_legend},foo')
        );

        $this->assertSame(
            '{config_legend},foo,bar;{foo_legend},baz',
            $pm->applyToString('{config_legend},foo;{foo_legend},baz')
        );

        $this->assertSame(
            '{foo_legend},baz;{config_legend},bar',
            $pm->applyToString('{foo_legend},baz')
        );
    }

    /**
     * Tests adding a legend before another legend.
     */
    public function testAddsALegendBeforeAnotherLegend(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('config_legend', 'foo_legend', PaletteManipulator::POSITION_BEFORE)
            ->addField('foo', 'config_legend', PaletteManipulator::POSITION_APPEND)
        ;

        $this->assertSame(
            '{config_legend},foo;{foo_legend},baz',
            $pm->applyToString('{foo_legend},baz')
        );

        $this->assertSame(
            '{bar_legend},baz;{config_legend},foo',
            $pm->applyToString('{bar_legend},baz')
        );
    }

    /**
     * Tests adding a legend after another legend.
     */
    public function testAddsALegendAfterAnotherLegend(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('config_legend', 'foo_legend', PaletteManipulator::POSITION_AFTER)
            ->addField('foo', 'config_legend')
        ;

        $this->assertSame(
            '{foo_legend},baz;{config_legend},foo',
            $pm->applyToString('{foo_legend},baz')
        );

        $this->assertSame(
            '{bar_legend},baz;{config_legend},foo',
            $pm->applyToString('{bar_legend},baz')
        );
    }

    /**
     * Tests adding a field before another field.
     */
    public function testAddsAFieldBeforeAnotherField(): void
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', 'foo', PaletteManipulator::POSITION_BEFORE)
        ;

        $this->assertSame(
            '{config_legend},bar,foo',
            $pm->applyToString('{config_legend},foo')
        );

        $this->assertSame(
            '{config_legend},baz,bar',
            $pm->applyToString('{config_legend},baz')
        );
    }

    /**
     * Tests adding a field after another field.
     */
    public function testAddsAFieldAfterAnotherField(): void
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', 'foo', PaletteManipulator::POSITION_AFTER)
        ;

        $this->assertSame(
            '{config_legend},foo,bar',
            $pm->applyToString('{config_legend},foo')
        );

        $this->assertSame(
            '{config_legend},baz,bar',
            $pm->applyToString('{config_legend},baz')
        );
    }

    /**
     * Tests skipping legends.
     */
    public function testSkipsTheLegendsIfConfigured(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('foobar_legend', '', PaletteManipulator::POSITION_APPEND)
            ->addField('field3', 'field1')
            ->addField('field4', 'field3')
            ->addField('field2', 'field1')
            ->addField('foobar', 'foobar_legend', PaletteManipulator::POSITION_APPEND)
            ->addField('first', '', PaletteManipulator::POSITION_PREPEND)
        ;

        $this->assertSame(
            'first,field1,field2,field3,field4,foobar',
            $pm->applyToString('field1', true)
        );
    }

    /**
     * Tests adding a field to multiple parents.
     */
    public function testAddsAFieldToMultipleParents(): void
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', ['baz', 'foo'])
        ;

        $this->assertSame(
            '{foobar_legend},foo,bar',
            $pm->applyToString('{foobar_legend},foo')
        );
    }

    /**
     * Tests adding a field to an empty palette.
     */
    public function testAddsAFieldToAnEmptyPalette(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('name_legend', '', PaletteManipulator::POSITION_PREPEND)
            ->addField('name', 'name_legend', PaletteManipulator::POSITION_APPEND)
        ;

        $this->assertSame(
            '{name_legend},name',
            $pm->applyToString('')
        );

        $pm = PaletteManipulator::create()
            ->addField('name', 'name_legend', PaletteManipulator::POSITION_APPEND)
        ;

        $this->assertSame(
            'name',
            $pm->applyToString('')
        );

        $pm = PaletteManipulator::create()
            ->addField('name', 'name_legend', PaletteManipulator::POSITION_APPEND, 'name_legend')
        ;

        $this->assertSame(
            '{name_legend},name',
            $pm->applyToString('')
        );
    }

    /**
     * Tests adding a field to a nameless legend.
     */
    public function testAddsAFieldToANamelessLegend(): void
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', 'foo', PaletteManipulator::POSITION_AFTER)
        ;

        $this->assertSame(
            '{name_legend},name;foo,bar',
            $pm->applyToString('{name_legend},name;foo')
        );
    }

    /**
     * Tests that empty legends are ignored.
     */
    public function testIgnoresEmptyLegends(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('empty_legend', '', PaletteManipulator::POSITION_APPEND)
            ->addField('foo', 'bar', PaletteManipulator::POSITION_BEFORE)
        ;

        $this->assertSame(
            '{foobar_legend},foo,bar',
            $pm->applyToString('{foobar_legend},bar')
        );
    }

    /**
     * Tests that duplicate legends are ignored.
     */
    public function testIgnoresDuplicateLegends(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('foobar_legend', '', PaletteManipulator::POSITION_APPEND)
            ->addField('bar', 'foo', PaletteManipulator::POSITION_AFTER)
        ;

        $this->assertSame(
            '{foobar_legend},foo,bar;{other_legend},other',
            $pm->applyToString('{foobar_legend},foo;{other_legend},other')
        );
    }

    /**
     * Tests adding collapsed legends.
     */
    public function testAddsHiddenLegends(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('foobar_legend', '', PaletteManipulator::POSITION_APPEND, true)
            ->addField(['foo', 'bar'], 'foobar_legend', PaletteManipulator::POSITION_APPEND)
        ;

        $this->assertSame(
            '{name_legend},name;{foobar_legend:hide},foo,bar',
            $pm->applyToString('{name_legend},name')
        );
    }

    /**
     * Tests applying the changes to a DCA palette.
     */
    public function testAppliesChangesToADcaPalette(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('foobar_legend', '', PaletteManipulator::POSITION_APPEND)
            ->addField(['foo', 'bar'], 'foobar_legend', PaletteManipulator::POSITION_APPEND)
        ;

        $GLOBALS['TL_DCA']['tl_test']['palettes']['default'] = '{name_legend},name';

        $pm->applyToPalette('default', 'tl_test');

        $this->assertSame(
            '{name_legend},name;{foobar_legend},foo,bar',
            $GLOBALS['TL_DCA']['tl_test']['palettes']['default']
        );
    }

    /**
     * Tests applying the changes to a DCA subpalette.
     */
    public function testAppliesChangesToADcaSubpalette(): void
    {
        $pm = PaletteManipulator::create()
            ->addField(['foo', 'bar'], 'lastname')
        ;

        $GLOBALS['TL_DCA']['tl_test']['subpalettes']['name'] = 'firstname,lastname';

        $pm->applyToSubpalette('name', 'tl_test');

        $this->assertSame(
            'firstname,lastname,foo,bar',
            $GLOBALS['TL_DCA']['tl_test']['subpalettes']['name']
        );
    }

    /**
     * Tests that the fallback creates a palette.
     */
    public function testAddsAFieldToTheFallbackPalette(): void
    {
        $pm = PaletteManipulator::create()
            ->addField(
                'bar',
                'foo',
                PaletteManipulator::POSITION_AFTER,
                'name_legend'
            )
        ;

        $this->assertSame(
            '{name_legend},name,bar',
            $pm->applyToString('{name_legend},name')
        );
    }

    /**
     * Tests the fallback callback.
     */
    public function testCallsTheFallbackClosure(): void
    {
        $closureCalled = false;

        $pm = PaletteManipulator::create()
            ->addField(
                'bar',
                'foo',
                PaletteManipulator::POSITION_AFTER,
                function (array $config, array $action, bool $skipLegends) use (&$closureCalled): void {
                    $closureCalled = true;

                    $this->assertInternalType('array', $config);
                    $this->assertInternalType('array', $action);
                    $this->assertInternalType('bool', $skipLegends);

                    $this->assertArrayHasKey('fields', $action);
                    $this->assertArrayHasKey('parents', $action);
                    $this->assertArrayHasKey('position', $action);
                }
            )
        ;

        $pm->applyToString('baz');

        $this->assertTrue($closureCalled);
    }

    /**
     * Tests applying changes to a missing palette.
     */
    public function testFailsIfTheDcaPaletteDoesNotExist(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('foobar_legend', '', PaletteManipulator::POSITION_APPEND)
            ->addField(['foo', 'bar'], 'foobar_legend', PaletteManipulator::POSITION_APPEND)
        ;

        // Make sure the palette is not here (for whatever reason another test might have set it)
        unset($GLOBALS['TL_DCA']['tl_test']['palettes']['default']);

        $this->expectException('InvalidArgumentException');

        $pm->applyToPalette('default', 'tl_test');
    }

    /**
     * Tests applying changes to a missing subpalette.
     */
    public function testFailsIfTheDcaSubpaletteDoesNotExist(): void
    {
        $pm = PaletteManipulator::create()
            ->addField(['foo', 'bar'], 'lastname')
        ;

        // Make sure the palette is not here (for whatever reason another test might have set it)
        unset($GLOBALS['TL_DCA']['tl_test']['subpalettes']['name']);

        $this->expectException('InvalidArgumentException');

        $pm->applyToSubpalette('name', 'tl_test');
    }

    /**
     * Tests adding a field at an invalid position.
     */
    public function testFailsIfThePositionIsInvalid(): void
    {
        $this->expectException('LogicException');

        PaletteManipulator::create()
            ->addField('bar', 'foo', 'foo_position')
            ->applyToString('foo')
        ;
    }

    /**
     * Tests adding a field with a fallback at an invalid position.
     */
    public function testFailsIfTheFallbackPositionIsInvalid(): void
    {
        $this->expectException('InvalidArgumentException');

        PaletteManipulator::create()
            ->addField(
                'bar',
                'foo',
                PaletteManipulator::POSITION_AFTER,
                'foobar_legend',
                PaletteManipulator::POSITION_AFTER
            )
            ->applyToString('foo')
        ;
    }
}
