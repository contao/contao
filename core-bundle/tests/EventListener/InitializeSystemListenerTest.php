<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\Config;
use Contao\CoreBundle\Command\VersionCommand;
use Contao\CoreBundle\Config\FileLocator;
use Contao\Environment;
use Contao\CoreBundle\EventListener\InitializeSystemListener;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouterInterface;

/**
 * Tests the BootstrapLegacyListener class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class InitializeSystemListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new InitializeSystemListener(
            $this->getMock('Symfony\Component\Routing\RouterInterface'),
            $this->getRootDir()
        );

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\InitializeSystemListener', $listener);
    }

    /**
     * Test if $isBooted is set correctly
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPreventBootTwice()
    {
        global $kernel;

        /** @var Kernel $kernel */
        $kernel = $this->mockKernel();

        $listener = new InitializeSystemListener(
            $this->getMock('Symfony\Component\Routing\RouterInterface'),
            $this->getRootDir()
        );

        $ref = new \ReflectionClass('Contao\CoreBundle\EventListener\InitializeSystemListener');
        $boot = $ref->getMethod('boot');
        $boot->setAccessible(true);
        $boot->invoke($listener, null, null);

        $isBooted = $ref->getMethod('booted');
        $isBooted->setAccessible(true);
        $this->assertTrue($isBooted->invoke($listener));

        // invoke boot again
        $boot->invoke($listener, null, null);

    }

    /**
     * Tests a front end request.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFrontendRequest()
    {
        global $kernel;

        /** @var Kernel $kernel */
        $kernel    = $this->mockKernel();
        $container = $kernel->getContainer();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/index.html'),
            $this->getRootDir() . '/app'
        );

        $listener->setContainer($container);

        $container->enterScope('frontend');

        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertEquals('FE', TL_MODE);
        $this->assertEquals('index.html', TL_SCRIPT);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
    }

    /**
     * Tests a back end request.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testBackendRequest()
    {
        global $kernel;

        /** @var Kernel $kernel */
        $kernel    = $this->mockKernel();
        $container = $kernel->getContainer();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/contao/install'),
            $this->getRootDir() . '/app'
        );

        $listener->setContainer($container);

        $container->enterScope('backend');

        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertEquals('BE', TL_MODE);
        $this->assertEquals('contao/install', TL_SCRIPT);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
    }

    /**
     * Tests a request without scope.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWithoutScope()
    {
        global $kernel;

        /** @var Kernel $kernel */
        $kernel    = $this->mockKernel();
        $container = $kernel->getContainer();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/index.html'),
            $this->getRootDir() . '/app'
        );

        $listener->setContainer($container);

        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertFalse(defined('TL_MODE'));
        $this->assertFalse(defined('TL_SCRIPT'));
        $this->assertFalse(defined('TL_ROOT'));
    }

    /**
     * Tests that the Contao framework is not initialized without a container.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWithoutContainer()
    {
        global $kernel;

        /** @var Kernel $kernel */
        $kernel = $this->mockKernel();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/index.html'),
            $this->getRootDir() . '/app'
        );

        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertFalse(defined('TL_MODE'));
        $this->assertFalse(defined('TL_SCRIPT'));
        $this->assertFalse(defined('TL_ROOT'));
    }

    /**
     * Tests that the Contao framework is not initialized for subrequests.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSubRequest()
    {
        global $kernel;

        /** @var Kernel $kernel */
        $kernel    = $this->mockKernel();
        $container = $kernel->getContainer();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/index.html'),
            $this->getRootDir() . '/app'
        );

        $listener->setContainer($container);

        $container->enterScope('frontend');

        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST));

        $this->assertFalse(defined('TL_MODE'));
        $this->assertFalse(defined('TL_SCRIPT'));
        $this->assertFalse(defined('TL_ROOT'));
    }

    /**
     * Tests a console command.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testConsoleCommand()
    {
        global $kernel;

        /** @var Kernel $kernel */
        $kernel = $this->mockKernel();

        $listener = new InitializeSystemListener(
            $this->getMock('Symfony\Component\Routing\RouterInterface'),
            $this->getRootDir() . '/app'
        );

        $listener->onConsoleCommand(
            new ConsoleCommandEvent(new VersionCommand(), new StringInput(''), new ConsoleOutput())
        );

        $this->assertEquals('FE', TL_MODE);
        $this->assertEquals('console', TL_SCRIPT);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
    }

    /**
     * Mocks a Contao kernel.
     *
     * @return Kernel The kernel mock object
     */
    private function mockKernel()
    {
        Config::set('bypassCache', true);
        Environment::set('httpAcceptLanguage', []);

        $kernel = $this->getMock(
            'Symfony\Component\HttpKernel\Kernel',
            [
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
            ],
            ['test', false]
        );

        $container = new Container();
        $container->addScope(new Scope('frontend'));
        $container->addScope(new Scope('backend'));

        $kernel
            ->expects($this->any())
            ->method('getContainer')
            ->willReturn($container)
        ;

        $container->set(
            'contao.resource_locator',
            new FileLocator([
                'TestBundle' => $this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao',
                'foobar'     => $this->getRootDir() . '/system/modules/foobar'
            ])
        );

        $container->set(
            'contao.cached_resource_locator',
            new FileLocator([
                'TestBundle' => $this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao',
                'foobar'     => $this->getRootDir() . '/system/modules/foobar'
            ])
        );

        return $kernel;
    }

    /**
     * Mocks a router returning the given URL.
     *
     * @param string $url The URL to return
     *
     * @return RouterInterface The router object
     */
    private function mockRouter($url)
    {
        $router = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $router
            ->expects($this->any())
            ->method('generate')
            ->willReturn($url)
        ;

        return $router;
    }
}
