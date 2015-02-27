<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\EventListener\BootstrapLegacyListener;
use Contao\CoreBundle\HttpKernel\ContaoKernelInterface;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Tests the BootstrapLegacyListener class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class BootstrapLegacyListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new BootstrapLegacyListener(
            $this->getMock('Symfony\Component\Routing\RouterInterface'),
            $this->getRootDir()
        );

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\BootstrapLegacyListener', $listener);
    }

    /**
     * Test that console mode booting works flawless.
     */
    public function testOnBootLegacyForRequestFrontend()
    {
        global $kernel;
        /** @var ContaoKernelInterface kernel */
        $kernel = $this->mockKernel();

        $listener = new BootstrapLegacyListener(
            $this->mockRouter('/index.html'),
            $this->getRootDir() . '/app'
        );

        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'frontend');
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener->onBootLegacyForRequest($event);

        $this->assertEquals('FE', TL_MODE);
        $this->assertEquals('/index.html', TL_SCRIPT);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
    }

    /**
     * Test that there is an exception when no route has been set in the request.
     */
    public function testOnBootLegacyForRequestExceptionWhenNoRouteFound()
    {
        global $kernel;
        /** @var ContaoKernelInterface kernel */
        $kernel = $this->mockKernel();

        $listener = new BootstrapLegacyListener(
            $this->mockRouter('/index.html'),
            $this->getRootDir() . '/app'
        );

        $request = new Request();
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $exception = null;
        try {
            $listener->onBootLegacyForRequest($event);
        } catch (\Exception $e) {
            $exception = $e;
        }

        $this->isInstanceOf('Symfony\\Component\\Routing\\Exception\\RouteNotFoundException', $exception);
    }

    /**
     * Test that console mode booting works flawless.
     */
    public function testOnBootLegacyForRequestFrontendFallback()
    {
        global $kernel;
        /** @var ContaoKernelInterface kernel */
        $kernel = $this->mockKernel();

        $listener = new BootstrapLegacyListener(
            $this->mockRouter('/index.html'),
            $this->getRootDir() . '/app'
        );

        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener->onBootLegacyForRequest($event);

        $this->assertEquals('FE', TL_MODE);
        $this->assertEquals('/index.html', TL_SCRIPT);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
    }

    /**
     * Test that console mode booting works flawless.
     */
    public function testOnBootLegacyForRequestBackend()
    {
        global $kernel;
        /** @var ContaoKernelInterface kernel */
        $kernel = $this->mockKernel();

        $listener = new BootstrapLegacyListener(
            $this->mockRouter('/contao/install'),
            $this->getRootDir() . '/app'
        );

        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'backend');
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener->onBootLegacyForRequest($event);

        $this->assertEquals('BE', TL_MODE);
        $this->assertEquals('/contao/install', TL_SCRIPT);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
    }

    /**
     * Test that console mode booting works flawless.
     */
    public function testOnBootLegacyForConsole()
    {
        global $kernel;
        /** @var ContaoKernelInterface kernel */
        $kernel = $this->mockKernel();

        $listener = new BootstrapLegacyListener(
            $this->getMock('Symfony\Component\Routing\RouterInterface'),
            $this->getRootDir() . '/app'
        );

        $listener->onBootLegacyForConsole();

        $this->assertEquals('FE', TL_MODE);
        $this->assertEquals('console', TL_SCRIPT);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
    }

    /**
     * Mock a kernel for use in tests.
     *
     * @return ContaoKernelInterface
     */
    private function mockKernel()
    {
        \Contao\Config::clear([
            'bypassCache' => true
        ]);
        \Contao\Environment::clear([
            'httpAcceptLanguage' => []
        ]);

        $kernel = $this->getMock(
            'Contao\CoreBundle\HttpKernel\ContaoKernelInterface',
            [
                // ContaoKernelInterface
                'addAutoloadBundles',
                'writeBundleCache',
                'loadBundleCache',
                'getContaoBundles',
                // KernelInterface
                'registerBundles',
                'registerContainerConfiguration',
                'boot',
                'shutdown',
                'getBundles',
                'isClassInActiveBundle',
                'getBundle',
                'locateResource',
                'getName',
                'getEnvironment',
                'isDebug',
                'getRootDir',
                'getContainer',
                'getStartTime',
                'getCacheDir',
                'getLogDir',
                'getCharset',
                // HttpKernelInterface
                'handle',
                // Serializable
                'serialize',
                'unserialize',
            ]
        );
        $kernel
            ->expects($this->any())
            ->method('getContaoBundles')
            ->willReturn(array());

        return $kernel;
    }

    /**
     * Mock a router returning always the same url.
     *
     * @param string $url The url to be returned when generate() will get called.
     *
     * @return RouterInterface
     */
    private function mockRouter($url)
    {
        $router = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $router
            ->expects($this->any())
            ->method('generate')
            ->willReturn($url);

        return $router;
    }
}