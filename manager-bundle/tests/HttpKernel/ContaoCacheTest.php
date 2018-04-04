<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\HttpKernel;

use Contao\ManagerBundle\HttpKernel\ContaoCache;
use Contao\TestCase\ContaoTestCase;
use FOS\HttpCache\SymfonyCache\Events;
use Symfony\Component\HttpKernel\KernelInterface;
use Terminal42\HeaderReplay\SymfonyCache\HeaderReplaySubscriber;

class ContaoCacheTest extends ContaoTestCase
{
    public function testInstantiation(): void
    {
        $cache = new ContaoCache($this->createMock(KernelInterface::class), $this->getTempDir());

        $this->assertInstanceOf('Contao\ManagerBundle\HttpKernel\ContaoCache', $cache);
        $this->assertInstanceOf('Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache', $cache);
    }

    public function testAddsTheEventSubscribers(): void
    {
        $cache = new ContaoCache($this->createMock(KernelInterface::class), $this->getTempDir());
        $dispatcher = $cache->getEventDispatcher();
        $preHandleListeners = $dispatcher->getListeners(Events::PRE_HANDLE);
        $headerReplayListener = $preHandleListeners[0][0];

        $this->assertInstanceOf(HeaderReplaySubscriber::class, $headerReplayListener);

        $reflection = new \ReflectionClass($headerReplayListener);
        $optionsProperty = $reflection->getProperty('options');
        $optionsProperty->setAccessible(true);
        $options = $optionsProperty->getValue($headerReplayListener);

        $this->assertSame(
            [
                'user_context_headers' => [
                    'cookie',
                    'authorization',
                ],
                'ignore_cookies' => ['/^csrf_./'],
            ],
            $options
        );
    }
}
