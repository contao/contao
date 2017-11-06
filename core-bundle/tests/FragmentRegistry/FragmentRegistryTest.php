<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Fragment;

use Contao\CoreBundle\Fragment\FragmentRegistry;
use PHPUnit\Framework\TestCase;

class FragmentRegistryTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $registry = new FragmentRegistry();

        $this->assertInstanceOf('Contao\CoreBundle\Fragment\FragmentRegistry', $registry);
    }

    public function testChecksTheBasicOptions(): void
    {
        $registry = new FragmentRegistry();

        $this->expectException('InvalidArgumentException');

        $registry->addFragment('foobar', new \stdClass(), ['nonsense' => 'test']);
    }

    public function testReadsAndWritesFragments(): void
    {
        $fragment = new \stdClass();

        $options = [
            'tag' => 'test',
            'type' => 'test',
            'controller' => 'test',
        ];

        $registry = new FragmentRegistry();
        $registry->addFragment('foobar', $fragment, $options);

        $this->assertSame($fragment, $registry->getFragment('foobar'));
    }

    public function testReadsAndWritesOptions(): void
    {
        $fragment = new \stdClass();

        $options = [
            'tag' => 'test',
            'type' => 'test',
            'controller' => 'test',
            'whatever' => 'more',
        ];

        $registry = new FragmentRegistry();
        $registry->addFragment('foobar', $fragment, $options);

        $this->assertSame($options, $registry->getOptions('foobar'));
    }

    public function testReturnsTheFragments(): void
    {
        $fragment = new \stdClass();

        $options = [
            'tag' => 'test',
            'type' => 'test',
            'controller' => 'test',
            'whatever' => 'more',
        ];

        $registry = new FragmentRegistry();
        $registry->addFragment('foobar', $fragment, $options);

        $this->assertCount(1, $registry->getFragments());
    }

    public function testSupportsFilteringFragments(): void
    {
        $fragment = new \stdClass();

        $options = [
            'tag' => 'test',
            'type' => 'test',
            'controller' => 'test',
            'whatever' => 'more',
        ];

        $registry = new FragmentRegistry();
        $registry->addFragment('foobar', $fragment, $options);

        $this->assertCount(0, $registry->getFragments(function () { return false; }));
    }
}
