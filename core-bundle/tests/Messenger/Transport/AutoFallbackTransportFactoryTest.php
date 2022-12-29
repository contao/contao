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
use Contao\CoreBundle\Messenger\Transport\AutoFallbackTransport;
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
        $notifier = new AutoFallbackNotifier($this->createMock(CacheItemPoolInterface::class));
        $factory = new AutoFallbackTransportFactory($notifier, new Container());

        $this->assertTrue($factory->supports('contao_auto_fallback://contao_prio_low?fallback=sync', []));
        $this->assertFalse($factory->supports('doctrine://default', []));
    }

    public function testCreatesTransport(): void
    {
        $target = $this->createMock(TransportInterface::class);
        $fallback = $this->createMock(TransportInterface::class);

        $container = new Container();
        $container->set('contao_prio_low', $target);
        $container->set('sync', $fallback);

        $notifier = new AutoFallbackNotifier($this->createMock(CacheItemPoolInterface::class));
        $factory = new AutoFallbackTransportFactory($notifier, $container);

        /** @var AutoFallbackTransport $transport */
        $transport = $factory->createTransport(
            'contao_auto_fallback://contao_prio_low?fallback=sync',
            [],
            $this->createMock(SerializerInterface::class)
        );

        $this->assertSame('contao_prio_low', $transport->getTargetTransportName());
        $this->assertSame($target, $transport->getTarget());
        $this->assertSame($fallback, $transport->getFallback());
    }

    public function testFailsIfTargetDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given Auto Fallback Transport target "contao_prio_low" is invalid.');

        $container = new Container();
        $container->set('sync', $this->createMock(TransportInterface::class));

        $notifier = new AutoFallbackNotifier($this->createMock(CacheItemPoolInterface::class));
        $factory = new AutoFallbackTransportFactory($notifier, $container);
        $factory->createTransport(
            'contao_auto_fallback://contao_prio_low?fallback=sync',
            [],
            $this->createMock(SerializerInterface::class)
        );
    }

    public function testFailsIfFallbackDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given Auto Fallback Transport fallback "sync" is invalid.');

        $container = new Container();
        $container->set('contao_prio_low', $this->createMock(TransportInterface::class));

        $notifier = new AutoFallbackNotifier($this->createMock(CacheItemPoolInterface::class));
        $factory = new AutoFallbackTransportFactory($notifier, $container);
        $factory->createTransport(
            'contao_auto_fallback://contao_prio_low?fallback=sync',
            [],
            $this->createMock(SerializerInterface::class)
        );
    }
}
