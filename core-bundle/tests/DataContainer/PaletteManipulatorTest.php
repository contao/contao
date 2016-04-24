<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the PaletteManipulator class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class PaletteManipulatorTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $pm = PaletteManipulator::create();

        static::assertInstanceOf('Contao\CoreBundle\DataContainer\PaletteManipulator', $pm);
    }

    /**
     * Tests prepending a field.
     */
    public function testPrependFieldToPalette()
    {
        $pm = PaletteManipulator::create()
            ->addField('foo', 'config_legend', PaletteManipulator::POSITION_PREPEND, 'config_legend')
        ;

        $this->assertEquals(
            '{config_legend},foo,bar',
            $pm->applyToString('{config_legend},bar')
        );

        $this->assertEquals(
            '{config_legend},foo,bar;{foo_legend},baz',
            $pm->applyToString('{config_legend},bar;{foo_legend},baz')
        );

        $this->assertEquals(
            '{foo_legend},baz;{config_legend},foo',
            $pm->applyToString('{foo_legend},baz')
        );
    }

    /**
     * Tests appending a field.
     */
    public function testAppendFieldToPalette()
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', 'config_legend', PaletteManipulator::POSITION_APPEND, 'config_legend')
        ;

        $this->assertEquals(
            '{config_legend},foo,bar',
            $pm->applyToString('{config_legend},foo')
        );

        $this->assertEquals(
            '{config_legend},foo,bar;{foo_legend},baz',
            $pm->applyToString('{config_legend},foo;{foo_legend},baz')
        );

        $this->assertEquals(
            '{foo_legend},baz;{config_legend},bar',
            $pm->applyToString('{foo_legend},baz')
        );
    }

    /**
     * Tests adding a legend before another legend.
     */
    public function testBeforeLegend()
    {
        $pm = PaletteManipulator::create()
            ->addLegend('config_legend', 'foo_legend', PaletteManipulator::POSITION_BEFORE)
            ->addField('foo', 'config_legend', PaletteManipulator::POSITION_APPEND)
        ;

        $this->assertEquals(
            '{config_legend},foo;{foo_legend},baz',
            $pm->applyToString('{foo_legend},baz')
        );

        $this->assertEquals(
            '{bar_legend},baz;{config_legend},foo',
            $pm->applyToString('{bar_legend},baz')
        );
    }

    /**
     * Tests adding a legend after another legend.
     */
    public function testAfterLegend()
    {
        $pm = PaletteManipulator::create()
            ->addLegend('config_legend', 'foo_legend', PaletteManipulator::POSITION_AFTER)
            ->addField('foo', 'config_legend')
        ;

        $this->assertEquals(
            '{foo_legend},baz;{config_legend},foo',
            $pm->applyToString('{foo_legend},baz')
        );

        $this->assertEquals(
            '{bar_legend},baz;{config_legend},foo',
            $pm->applyToString('{bar_legend},baz')
        );
    }

    /**
     * Tests adding a field before another field.
     */
    public function testBeforeField()
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', 'foo', PaletteManipulator::POSITION_BEFORE)
        ;

        $this->assertEquals(
            '{config_legend},bar,foo',
            $pm->applyToString('{config_legend},foo')
        );

        $this->assertEquals(
            '{config_legend},baz,bar',
            $pm->applyToString('{config_legend},baz')
        );
    }

    /**
     * Tests adding a field after another field.
     */
    public function testAfterField()
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', 'foo', PaletteManipulator::POSITION_AFTER)
        ;

        $this->assertEquals(
            '{config_legend},foo,bar',
            $pm->applyToString('{config_legend},foo')
        );

        $this->assertEquals(
            '{config_legend},baz,bar',
            $pm->applyToString('{config_legend},baz')
        );
    }

    /**
     * Tests skipping legends.
     */
    public function testSkipLegends()
    {
        $pm = PaletteManipulator::create()
            ->addLegend('foobar_legend', '', PaletteManipulator::POSITION_APPEND)
            ->addField('field3', 'field1')
            ->addField('field4', 'field3')
            ->addField('field2', 'field1')
            ->addField('foobar', 'foobar_legend', PaletteManipulator::POSITION_APPEND)
            ->addField('first', '', PaletteManipulator::POSITION_PREPEND)
        ;

        $this->assertEquals(
            'first,field1,field2,field3,field4,foobar',
            $pm->applyToString('field1', true)
        );
    }

    /**
     * Tests adding a field to multiple parents.
     */
    public function testMultipleParents()
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', ['baz', 'foo'])
        ;

        $this->assertEquals(
            '{foobar_legend},foo,bar',
            $pm->applyToString('{foobar_legend},foo')
        );
    }

    /**
     * Tests adding a field to an empty palette.
     */
    public function testAddFieldToEmptyPalette()
    {
        $pm = PaletteManipulator::create()
            ->addLegend('name_legend', '', PaletteManipulator::POSITION_PREPEND)
            ->addField('name', 'name_legend', PaletteManipulator::POSITION_APPEND)
        ;

        $this->assertEquals(
            '{name_legend},name',
            $pm->applyToString('')
        );

        $pm = PaletteManipulator::create()
            ->addField('name', 'name_legend', PaletteManipulator::POSITION_APPEND)
        ;

        $this->assertEquals(
            'name',
            $pm->applyToString('')
        );

        $pm = PaletteManipulator::create()
            ->addField('name', 'name_legend', PaletteManipulator::POSITION_APPEND, 'name_legend')
        ;

        $this->assertEquals(
            '{name_legend},name',
            $pm->applyToString('')
        );
    }

    /**
     * Tests adding a field to a nameless legend.
     */
    public function testAddToNamelessLegend()
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', 'foo', PaletteManipulator::POSITION_AFTER)
        ;

        $this->assertEquals(
            '{name_legend},name;foo,bar',
            $pm->applyToString('{name_legend},name;foo')
        );
    }

    /**
     * Tests that empty legends are ignored.
     */
    public function testIgnoresEmptyLegend()
    {
        $pm = PaletteManipulator::create()
            ->addLegend('empty_legend', '', PaletteManipulator::POSITION_APPEND)
            ->addField('foo', 'bar', PaletteManipulator::POSITION_BEFORE)
        ;

        $this->assertEquals(
            '{foobar_legend},foo,bar',
            $pm->applyToString('{foobar_legend},bar')
        );
    }

    /**
     * Tests that duplicate legends are ignored.
     */
    public function testIgnoresDuplicateLegend()
    {
        $pm = PaletteManipulator::create()
            ->addLegend('foobar_legend', '', PaletteManipulator::POSITION_APPEND)
            ->addField('bar', 'foo', PaletteManipulator::POSITION_AFTER)
        ;

        $this->assertEquals(
            '{foobar_legend},foo,bar;{other_legend},other',
            $pm->applyToString('{foobar_legend},foo;{other_legend},other')
        );
    }

    /**
     * Tests adding collapsed legends.
     */
    public function testHideLegend()
    {
        $pm = PaletteManipulator::create()
            ->addLegend('foobar_legend', '', PaletteManipulator::POSITION_APPEND, true)
            ->addField(['foo', 'bar'], 'foobar_legend', PaletteManipulator::POSITION_APPEND)
        ;

        $this->assertEquals(
            '{name_legend},name;{foobar_legend:hide},foo,bar',
            $pm->applyToString('{name_legend},name')
        );
    }

    /**
     * Tests applying the changes to a DCA palette.
     */
    public function testApplyToDcaPalette()
    {
        $pm = PaletteManipulator::create()
            ->addLegend('foobar_legend', '', PaletteManipulator::POSITION_APPEND)
            ->addField(['foo', 'bar'], 'foobar_legend', PaletteManipulator::POSITION_APPEND)
        ;

        $GLOBALS['TL_DCA']['tl_test']['palettes']['default'] = '{name_legend},name';

        $pm->applyToPalette('default', 'tl_test');

        $this->assertEquals(
            '{name_legend},name;{foobar_legend},foo,bar',
            $GLOBALS['TL_DCA']['tl_test']['palettes']['default']
        );
    }

    /**
     * Tests applying the changes to a DCA subpalette.
     */
    public function testApplyToDcaSubpalette()
    {
        $pm = PaletteManipulator::create()
            ->addField(['foo', 'bar'], 'lastname')
        ;

        $GLOBALS['TL_DCA']['tl_test']['subpalettes']['name'] = 'firstname,lastname';

        $pm->applyToSubpalette('name', 'tl_test');

        $this->assertEquals(
            'firstname,lastname,foo,bar',
            $GLOBALS['TL_DCA']['tl_test']['subpalettes']['name']
        );
    }

    /**
     * Tests that the fallback creates a palette.
     */
    public function testFallbackCreatesPalette()
    {
        $pm = PaletteManipulator::create()
            ->addField(
                'bar',
                'foo',
                PaletteManipulator::POSITION_AFTER,
                'name_legend'
            )
        ;

        $this->assertEquals(
            '{name_legend},name,bar',
            $pm->applyToString('{name_legend},name')
        );
    }

    /**
     * Tests the fallback callback.
     */
    public function testFallbackClosure()
    {
        $closureCalled = false;

        $pm = PaletteManipulator::create()
            ->addField(
                'bar',
                'foo',
                PaletteManipulator::POSITION_AFTER,
                function ($config, $action, $skipLegends) use (&$closureCalled) {
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
     *
     * @expectedException \InvalidArgumentException
     */
    public function testMissingDcaPalette()
    {
        $pm = PaletteManipulator::create()
            ->addLegend('foobar_legend', '', PaletteManipulator::POSITION_APPEND)
            ->addField(['foo', 'bar'], 'foobar_legend', PaletteManipulator::POSITION_APPEND)
        ;

        // Make sure the palette is not here (for whatever reason another test might have set it)
        unset($GLOBALS['TL_DCA']['tl_test']['palettes']['default']);

        $pm->applyToPalette('default', 'tl_test');
    }

    /**
     * Tests applying changes to a missing subpalette.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testMissingDcaSubpalette()
    {
        $pm = PaletteManipulator::create()
            ->addField(['foo', 'bar'], 'lastname')
        ;

        // Make sure the palette is not here (for whatever reason another test might have set it)
        unset($GLOBALS['TL_DCA']['tl_test']['subpalettes']['name']);

        $pm->applyToSubpalette('name', 'tl_test');
    }

    /**
     * Tests adding a field at an invalid position.
     *
     * @expectedException \LogicException
     */
    public function testInvalidPosition()
    {
        PaletteManipulator::create()
            ->addField('bar', 'foo', 'foo_position')
            ->applyToString('foo')
        ;
    }

    /**
     * Tests adding a field with a fallback at an invalid position.
     *
     * @expectedException \LogicException
     */
    public function testInvalidFallbackPosition()
    {
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
