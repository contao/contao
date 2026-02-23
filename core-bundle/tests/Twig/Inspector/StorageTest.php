<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Inspector;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Inspector\Storage;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;

class StorageTest extends TestCase
{
    public function testGetAndSetData(): void
    {
        $item = new CacheItem();
        $item->set(['foo' => ['a']]);

        $adapter = $this->createStub(AdapterInterface::class);
        $adapter
            ->method('getItem')
            ->with('contao.twig.inspector')
            ->willReturn($item)
        ;

        $storage = new Storage($adapter);

        $this->assertSame(['a'], $storage->get('foo'));
        $this->assertNull($storage->get('bar'));

        $storage->set('bar', ['b']);

        $this->assertSame(['b'], $storage->get('bar'));
    }
}
