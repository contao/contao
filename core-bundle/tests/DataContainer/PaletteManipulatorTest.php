<?php

/*
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the PaletteManipulator class
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

    public function testBeforeFieldToPalette()
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
     * @expectedException \UnderflowException
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
     * @expectedException \UnderflowException
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
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidPosition()
    {
        PaletteManipulator::create()
            ->addField('bar', 'foo', 'foo_position')
            ->applyToString('foo')
        ;
    }

    /**
     * @expectedException \InvalidArgumentException
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
            ->applyToString('foo');
        ;
    }
}
