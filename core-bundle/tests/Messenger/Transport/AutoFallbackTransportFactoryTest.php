<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Messenger\Transport;

use Contao\CoreBundle\Messenger\AutoFallbackNotifier;
use Contao\CoreBundle\Messenger\Transport\AutoFallbackTransportFactory;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class AutoFallbackTransportFactoryTest extends TestCase
{
    public function testSupports(): void
    {
        $notifier = new AutoFallbackNotifier($this->createMock(CacheItemPoolInterface::class), new Container());
        $factory = new AutoFallbackTransportFactory($notifier, new Container());

        $this->assertTrue($factory->supports('contao-auto-fallback://my_transport_name?target=target_transport&fallback=fallback_transport', []));
        $this->assertFalse($factory->supports('doctrine://default', []));
    }

    public function testCreatesTransport(): void
    {
        $self = $this->createMock(TransportInterface::class);
        $target = $this->createMock(TransportInterface::class);
        $fallback = $this->createMock(TransportInterface::class);

        $container = new Container();
        $container->set('my_transport_name', $self);
        $container->set('target_transport', $target);
        $container->set('fallback_transport', $fallback);

        $notifier = new AutoFallbackNotifier($this->createMock(CacheItemPoolInterface::class), new Container());
        $factory = new AutoFallbackTransportFactory($notifier, $container);

        $transport = $factory->createTransport(
            'contao-auto-fallback://my_transport_name?target=target_transport&fallback=fallback_transport',
            [],
            $this->createMock(SerializerInterface::class),
        );

        $this->assertSame('my_transport_name', $transport->getSelfTransportName());
        $this->assertSame($target, $transport->getTarget());
        $this->assertSame($fallback, $transport->getFallback());
    }

    public function testFailsIfSelfDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given Auto Fallback Transport self "my_transport_name" is invalid.');

        $container = new Container();
        $container->set('target_transport', $this->createMock(TransportInterface::class));
        $container->set('fallback_transport', $this->createMock(TransportInterface::class));

        $notifier = new AutoFallbackNotifier($this->createMock(CacheItemPoolInterface::class), new Container());
        $factory = new AutoFallbackTransportFactory($notifier, $container);

        $factory->createTransport(
            'contao-auto-fallback://my_transport_name?target=target_transport&fallback=fallback_transport',
            [],
            $this->createMock(SerializerInterface::class),
        );
    }

    public function testFailsIfTargetDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given Auto Fallback Transport target "target_transport" is invalid.');

        $container = new Container();
        $container->set('my_transport_name', $this->createMock(TransportInterface::class));
        $container->set('fallback_transport', $this->createMock(TransportInterface::class));

        $notifier = new AutoFallbackNotifier($this->createMock(CacheItemPoolInterface::class), new Container());
        $factory = new AutoFallbackTransportFactory($notifier, $container);

        $factory->createTransport(
            'contao-auto-fallback://my_transport_name?target=target_transport&fallback=fallback_transport',
            [],
            $this->createMock(SerializerInterface::class),
        );
    }

    public function testFailsIfFallbackDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given Auto Fallback Transport fallback "fallback_transport" is invalid.');

        $container = new Container();
        $container->set('my_transport_name', $this->createMock(TransportInterface::class));
        $container->set('target_transport', $this->createMock(TransportInterface::class));

        $notifier = new AutoFallbackNotifier($this->createMock(CacheItemPoolInterface::class), new Container());
        $factory = new AutoFallbackTransportFactory($notifier, $container);

        $factory->createTransport(
            'contao-auto-fallback://my_transport_name?target=target_transport&fallback=fallback_transport',
            [],
            $this->createMock(SerializerInterface::class),
        );
    }
}
