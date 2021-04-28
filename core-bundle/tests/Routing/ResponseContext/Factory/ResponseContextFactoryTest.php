<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\ResponseContext\Factory;

use Contao\CoreBundle\Routing\ResponseContext\Factory\Provider\ResponseContextProviderInterface;
use Contao\CoreBundle\Routing\ResponseContext\Factory\ResponseContextFactory;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use PHPUnit\Framework\TestCase;

class ResponseContextFactoryTest extends TestCase
{
    public function testTheResponseContextFactoryHandlesProvidersCorrectly(): void
    {
        $providerA = $this->createMock(ResponseContextProviderInterface::class);
        $providerA
            ->expects($this->exactly(2))
            ->method('supports')
            ->with('ProviderBClass')
            ->willReturn(false)
        ;

        $providerB = $this->createMock(ResponseContextProviderInterface::class);
        $providerB
            ->expects($this->exactly(2))
            ->method('supports')
            ->with('ProviderBClass')
            ->willReturn(true)
        ;
        $providerB
            ->expects($this->exactly(2))
            ->method('create')
            ->with('ProviderBClass')
            ->willReturn($this->createMock(ResponseContext::class))
        ;

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->once())
            ->method('setResponseContext')
            ->with($this->isInstanceOf(ResponseContext::class))
        ;

        $factory = new ResponseContextFactory([$providerA], $responseContextAccessor);

        $factory->addProvider($providerB);

        $this->assertInstanceOf(ResponseContext::class, $factory->create('ProviderBClass'));
        $this->assertInstanceOf(ResponseContext::class, $factory->createAndSetCurrent('ProviderBClass'));
    }

    public function testExceptionIfNoProviderSupportsTheContext(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No response context provider for "foobar" provided.');

        $factory = new ResponseContextFactory([], $this->createMock(ResponseContextAccessor::class));
        $factory->create('foobar');
    }
}
