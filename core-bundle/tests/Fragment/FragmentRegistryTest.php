<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fragment;

use Contao\CoreBundle\Fragment\FragmentConfig;
use Contao\CoreBundle\Fragment\FragmentRegistry;
use PHPUnit\Framework\TestCase;

class FragmentRegistryTest extends TestCase
{
    public function testReadsAndWritesTheFragmentConfiguration(): void
    {
        $registry = new FragmentRegistry();

        $this->assertEmpty($registry->all());
        $this->assertFalse($registry->has('foo.bar'));

        $config = new FragmentConfig('foo.bar');
        $registry->add('foo.bar', $config);

        $this->assertTrue($registry->has('foo.bar'));
        $this->assertSame($config, $registry->get('foo.bar'));
        $this->assertArrayHasKey('foo.bar', $registry->all());
        $this->assertSame(['foo.bar'], $registry->keys());

        $registry->remove('foo.bar');

        $this->assertEmpty($registry->all());
        $this->assertFalse($registry->has('foo.bar'));
    }
}
