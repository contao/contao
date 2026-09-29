<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\ApiPlatform\Metadata;

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use Contao\ApiBundle\ApiPlatform\Metadata\VirtualFilesystemResourceNameCollectionFactory;
use Contao\ApiBundle\Dto\VirtualFilesystemItem;
use PHPUnit\Framework\TestCase;

final class VirtualFilesystemResourceNameCollectionFactoryTest extends TestCase
{
    public function testAppendsTheVirtualFilesystemResourceName(): void
    {
        $decorated = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $decorated
            ->method('create')
            ->willReturn(new ResourceNameCollection(['App\\Entity\\Foo']))
        ;

        $factory = new VirtualFilesystemResourceNameCollectionFactory($decorated);

        $this->assertSame(
            ['App\\Entity\\Foo', VirtualFilesystemItem::class],
            iterator_to_array($factory->create()),
        );
    }
}
