<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Fragment\Reference;

use Contao\CoreBundle\Fragment\FragmentConfig;
use Contao\CoreBundle\Tests\TestCase;

class FragmentConfigTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $config = new FragmentConfig('');

        $this->assertInstanceOf('Contao\CoreBundle\Fragment\FragmentConfig', $config);
    }

    public function testReadsAndWritesTheController(): void
    {
        $config = new FragmentConfig('foo');

        $this->assertSame('foo', $config->getController());

        $config->setController('bar');

        $this->assertSame('bar', $config->getController());
    }

    public function testReadsAndWritesTheRenderer(): void
    {
        $config = new FragmentConfig('', 'foo');

        $this->assertSame('foo', $config->getRenderer());

        $config->setRenderer('bar');

        $this->assertSame('bar', $config->getRenderer());
    }

    public function testReadsAndWritesTheOptions(): void
    {
        $config = new FragmentConfig('', '', ['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $config->getOptions());

        $config->setOptions(['foo' => 'baz']);

        $this->assertSame(['foo' => 'baz'], $config->getOptions());
        $this->assertSame('baz', $config->getOption('foo'));

        $config->setOption('foo', 'bar');

        $this->assertSame('bar', $config->getOption('foo'));
    }

    public function testReturnsNullIfAnOptionIsNotSet(): void
    {
        $config = new FragmentConfig('', '', ['foo' => 'bar']);

        $this->assertSame('bar', $config->getOption('foo'));
        $this->assertNull($config->getOption('bar'));
    }
}
