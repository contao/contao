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
        $pm = new PaletteManipulator('', [], '');

        static::assertInstanceOf('Contao\CoreBundle\DataContainer\PaletteManipulator', $pm);
    }

    public function testPrependFieldToPalette()
    {
        $pm = PaletteManipulator::prepend('config_legend', 'foo');

        static::assertEquals(
            '{config_legend},foo,bar',
            $pm->applyTo('{config_legend},bar')
        );

        static::assertEquals(
            '{config_legend},foo,bar;{foo_legend},baz',
            $pm->applyTo('{config_legend},bar;{foo_legend},baz')
        );

        static::assertEquals(
            '{foo_legend},baz;{config_legend},foo',
            $pm->applyTo('{foo_legend},baz')
        );
    }

    public function testAppendFieldToPalette()
    {
        $pm = PaletteManipulator::append('config_legend', 'bar');

        static::assertEquals(
            '{config_legend},foo,bar',
            $pm->applyTo('{config_legend},foo')
        );

        static::assertEquals(
            '{config_legend},foo,bar;{foo_legend},baz',
            $pm->applyTo('{config_legend},foo;{foo_legend},baz')
        );

        static::assertEquals(
            '{foo_legend},baz;{config_legend},bar',
            $pm->applyTo('{foo_legend},baz')
        );
    }

    public function testBeforeLegend()
    {
        $pm = PaletteManipulator::beforeLegend('foo_legend', 'config_legend', 'foo');

        static::assertEquals(
            '{config_legend},foo;{foo_legend},baz',
            $pm->applyTo('{foo_legend},baz')
        );

        static::assertEquals(
            '{bar_legend},baz;{config_legend},foo',
            $pm->applyTo('{bar_legend},baz')
        );
    }

    public function testAfterLegend()
    {
        $pm = PaletteManipulator::afterLegend('foo_legend', 'config_legend', 'foo');

        static::assertEquals(
            '{foo_legend},baz;{config_legend},foo',
            $pm->applyTo('{foo_legend},baz')
        );

        static::assertEquals(
            '{bar_legend},baz;{config_legend},foo',
            $pm->applyTo('{bar_legend},baz')
        );
    }

    public function testBeforeField()
    {
        $pm = PaletteManipulator::beforeField('foo', 'bar');

        static::assertEquals(
            '{config_legend},bar,foo',
            $pm->applyTo('{config_legend},foo')
        );

        static::assertEquals(
            '{config_legend},baz;bar',
            $pm->applyTo('{config_legend},baz')
        );
    }

    public function testAfterField()
    {
        $pm = PaletteManipulator::afterField('foo', 'bar');

        static::assertEquals(
            '{config_legend},baz;bar',
            $pm->applyTo('{config_legend},baz')
        );
    }

    public function testFallback()
    {
        $pm = PaletteManipulator::beforeField('foo', 'bar');
        $fallback = PaletteManipulator::prepend('config_legend', 'bar');

        static::assertEquals(
            '{config_legend},bar,baz',
            $pm->setFallback($fallback)->applyTo('{config_legend},baz')
        );
    }
}
