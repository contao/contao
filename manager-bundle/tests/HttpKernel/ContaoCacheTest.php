<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Tests\HttpKernel;

use Contao\ManagerBundle\HttpKernel\ContaoCache;
use FOS\HttpCache\SymfonyCache\Events;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Terminal42\HeaderReplay\SymfonyCache\HeaderReplaySubscriber;

class ContaoCacheTest extends TestCase
{
    /**
     * @var ContaoCache
     */
    private $cache;

    /**
     * @var string
     */
    private $tmpdir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpdir = sys_get_temp_dir().'/'.uniqid('BundleCacheClearerTest_', false);

        $fs = new Filesystem();
        $fs->mkdir($this->tmpdir);

        $this->cache = new ContaoCache($this->createMock(KernelInterface::class), $this->tmpdir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $fs = new Filesystem();
        $fs->remove($this->tmpdir);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf('Contao\ManagerBundle\HttpKernel\ContaoCache', $this->cache);
        $this->assertInstanceOf('Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache', $this->cache);
    }

    public function testAddsTheEventSubscribers(): void
    {
        $dispatcher = $this->cache->getEventDispatcher();
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
