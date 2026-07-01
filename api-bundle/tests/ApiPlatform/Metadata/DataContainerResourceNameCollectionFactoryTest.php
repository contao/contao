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
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\ApiPlatform\Metadata\DataContainerResourceNameCollectionFactory;
use PHPUnit\Framework\TestCase;

final class DataContainerResourceNameCollectionFactoryTest extends TestCase
{
    public function testAppendsTheDataContainerRecordResourceName(): void
    {
        $decorated = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $decorated
            ->expects($this->once())
            ->method('create')
            ->willReturn(new ResourceNameCollection(['App\\Entity\\Foo']))
        ;

        $factory = new DataContainerResourceNameCollectionFactory($decorated);
        $resourceNames = iterator_to_array($factory->create());

        $this->assertSame(
            [
                'App\\Entity\\Foo',
                DataContainerRecord::class,
            ],
            $resourceNames,
        );
    }
}
