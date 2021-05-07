<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\ResponseContext\Factory\Provider;

use Contao\CoreBundle\Routing\ResponseContext\Factory\Provider\CoreProvider;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\TerminateResponseContextEvent;
use Contao\CoreBundle\Routing\ResponseContext\WebpageResponseContext;
use Contao\CoreBundle\Tests\Fixtures\Routing\FooResponseContext;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Response;

class CoreProviderTest extends TestCase
{
    public function testSupports(): void
    {
        $provider = new CoreProvider();

        $this->assertTrue($provider->supports(ResponseContext::class));
        $this->assertTrue($provider->supports(WebpageResponseContext::class));
        $this->assertFalse($provider->supports(FooResponseContext::class));
    }

    public function testCreate(): void
    {
        $provider = new CoreProvider();

        $this->assertInstanceOf(ResponseContext::class, $provider->create(ResponseContext::class));
        $this->assertInstanceOf(WebpageResponseContext::class, $provider->create(WebpageResponseContext::class));
    }

    public function testWithEventDispatcher(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(TerminateResponseContextEvent::class))
        ;

        $provider = new CoreProvider(new ServiceLocator([
            'event_dispatcher' => static function () use ($eventDispatcher) {
                return $eventDispatcher;
            },
        ]));

        $context = $provider->create(ResponseContext::class);

        $context->terminate(new Response());
    }
}
