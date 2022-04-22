<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\ResponseContext;

use Contao\CoreBundle\Event\JsonLdEvent;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ResponseContextTest extends TestCase
{
    public function testCanAddAndGetServices(): void
    {
        $context = new ResponseContext();

        $this->assertFalse($context->has(HtmlHeadBag::class));

        $context->add(new HtmlHeadBag());

        $this->assertTrue($context->has(HtmlHeadBag::class));
        $this->assertInstanceOf(HtmlHeadBag::class, $context->get(HtmlHeadBag::class));
    }

    public function testLazyServices(): void
    {
        $context = new ResponseContext();

        $this->assertFalse($context->has(HtmlHeadBag::class));

        $context->addLazy(HtmlHeadBag::class, static fn () => new HtmlHeadBag());

        $this->assertTrue($context->has(HtmlHeadBag::class));
        $this->assertInstanceOf(HtmlHeadBag::class, $context->get(HtmlHeadBag::class));
    }

    public function testLazyServicesAreNotDuplicated(): void
    {
        $context = new ResponseContext();
        $context->addLazy(ResponseHeaderBag::class, static fn () => new ResponseHeaderBag());

        $this->assertTrue($context->has(ResponseHeaderBag::class));
        $this->assertTrue($context->has(HeaderBag::class));

        $original = $context->get(ResponseHeaderBag::class);
        $parent = $context->get(HeaderBag::class);

        $this->assertInstanceOf(ResponseHeaderBag::class, $original);
        $this->assertInstanceOf(ResponseHeaderBag::class, $parent);
        $this->assertSame($original, $parent);
    }

    public function testLastServiceWins(): void
    {
        $context = new ResponseContext();

        $this->assertFalse($context->has(ParameterBag::class));

        $context->addLazy(ServerBag::class, static fn () => new ServerBag());
        $context->addLazy(InputBag::class, static fn () => new InputBag());

        $this->assertInstanceOf(ServerBag::class, $context->get(ServerBag::class));
        $this->assertInstanceOf(InputBag::class, $context->get(ParameterBag::class));
    }

    public function testGettingANonExistentServiceThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $context = new ResponseContext();
        $context->get(HtmlHeadBag::class);
    }

    public function testCheckIfIsInitalizedWorksCorrectly(): void
    {
        $context = new ResponseContext();
        $this->assertFalse($context->isInitialized(HtmlHeadBag::class));

        $context->addLazy(HtmlHeadBag::class, static fn () => new HtmlHeadBag());
        $this->assertFalse($context->isInitialized(HtmlHeadBag::class));

        $context->get(HtmlHeadBag::class);
        $this->assertTrue($context->isInitialized(HtmlHeadBag::class));
    }

    public function testInterfacesAndParentsAreAutomaticallyAddedAsAliases(): void
    {
        $context = new ResponseContext();

        // Using some anonymous classes here, so we don't have to create nonsense classes implementing nonsense
        // interfaces here. We took the BundleInterface as that is very unlikely to change.
        $serviceA = new class() extends Bundle {
        };

        $serviceB = new class() extends Bundle {
        };

        $serviceAClassname = $serviceA::class;
        $serviceBClassname = $serviceB::class;

        $context->add($serviceA);

        $this->assertSame($serviceA, $context->get($serviceAClassname));
        $this->assertSame($serviceA, $context->get(BundleInterface::class));
        $this->assertSame($serviceA, $context->get(Bundle::class));

        $context->add($serviceB);

        $this->assertSame($serviceA, $context->get($serviceAClassname));
        $this->assertSame($serviceB, $context->get($serviceBClassname));
        $this->assertSame($serviceB, $context->get(BundleInterface::class)); // Service B was added later
        $this->assertSame($serviceB, $context->get(Bundle::class)); // Service B was added later
    }

    public function testHeaderBagIsInitializedCompletelyEmpty(): void
    {
        $context = new ResponseContext();

        $this->assertCount(0, $context->getHeaderBag()->all());
    }

    public function testDispatchEvent(): void
    {
        $context = new ResponseContext();

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                function (JsonLdEvent $event) use ($context) {
                    $this->assertSame($context, $event->getResponseContext());

                    return true;
                }
            ))
        ;

        $context->add($eventDispatcher);
        $context->dispatchEvent(new JsonLdEvent());
    }
}
