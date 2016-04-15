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

        static::assertEquals(
            '{config_legend},foo,bar',
            $pm->applyToString('{config_legend},bar')
        );

        static::assertEquals(
            '{config_legend},foo,bar;{foo_legend},baz',
            $pm->applyToString('{config_legend},bar;{foo_legend},baz')
        );

        static::assertEquals(
            '{foo_legend},baz;{config_legend},foo',
            $pm->applyToString('{foo_legend},baz')
        );
    }

    public function testAppendFieldToPalette()
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', 'config_legend', PaletteManipulator::POSITION_APPEND, 'config_legend')
        ;

        static::assertEquals(
            '{config_legend},foo,bar',
            $pm->applyToString('{config_legend},foo')
        );

        static::assertEquals(
            '{config_legend},foo,bar;{foo_legend},baz',
            $pm->applyToString('{config_legend},foo;{foo_legend},baz')
        );

        static::assertEquals(
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

        static::assertEquals(
            '{config_legend},foo;{foo_legend},baz',
            $pm->applyToString('{foo_legend},baz')
        );

        static::assertEquals(
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

        static::assertEquals(
            '{foo_legend},baz;{config_legend},foo',
            $pm->applyToString('{foo_legend},baz')
        );

        static::assertEquals(
            '{bar_legend},baz;{config_legend},foo',
            $pm->applyToString('{bar_legend},baz')
        );
    }

    public function testBeforeField()
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', 'foo', PaletteManipulator::POSITION_BEFORE)
        ;

        static::assertEquals(
            '{config_legend},bar,foo',
            $pm->applyToString('{config_legend},foo')
        );

        static::assertEquals(
            '{config_legend},baz,bar',
            $pm->applyToString('{config_legend},baz')
        );
    }

    public function testAfterField()
    {
        $pm = PaletteManipulator::create()
            ->addField('bar', 'foo', PaletteManipulator::POSITION_AFTER)
        ;

        static::assertEquals(
            '{config_legend},foo,bar',
            $pm->applyToString('{config_legend},foo')
        );

        static::assertEquals(
            '{config_legend},baz,bar',
            $pm->applyToString('{config_legend},baz')
        );
    }
}
