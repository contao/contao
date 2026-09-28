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
use Contao\ApiBundle\ApiPlatform\Metadata\UserTemplateResourceNameCollectionFactory;
use Contao\ApiBundle\Dto\UserTemplate;
use PHPUnit\Framework\TestCase;

final class UserTemplateResourceNameCollectionFactoryTest extends TestCase
{
    public function testAppendsTheUserTemplateResourceName(): void
    {
        $decorated = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $decorated
            ->expects($this->once())
            ->method('create')
            ->willReturn(new ResourceNameCollection(['App\\Entity\\Foo']))
        ;

        $factory = new UserTemplateResourceNameCollectionFactory($decorated);
        $resourceNames = iterator_to_array($factory->create());

        $this->assertSame(
            [
                'App\\Entity\\Foo',
                UserTemplate::class,
            ],
            $resourceNames,
        );
    }

    public function testDoesNotAppendTheUserTemplateResourceNameIfTheTemplateStudioIsDisabled(): void
    {
        $resourceNames = new ResourceNameCollection(['App\\Entity\\Foo']);

        $decorated = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $decorated
            ->expects($this->once())
            ->method('create')
            ->willReturn($resourceNames)
        ;

        $factory = new UserTemplateResourceNameCollectionFactory($decorated, false);

        $this->assertSame($resourceNames, $factory->create());
    }
}
