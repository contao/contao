<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\Command\VersionCommand;
use Contao\CoreBundle\EventListener\InitializeSystemListener;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Tests the BootstrapLegacyListener class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 * @author Yanick Witschi <https://github.com/toflar>
 */
class InitializeSystemListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new InitializeSystemListener(
            $this->getMock('Symfony\\Component\\Routing\\RouterInterface'),
            $this->getMock('Symfony\Component\Security\Csrf\CsrfTokenManagerInterface'),
            $this->mockSession(),
            $this->getRootDir(),
            'contao_csrf_token'
        );

        $this->assertInstanceOf('Contao\\CoreBundle\\EventListener\\InitializeSystemListener', $listener);
    }

    /**
     * Tests a front end request.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFrontendRequest()
    {
        /** @var KernelInterface $kernel */
        global $kernel;

        $kernel = $this->mockKernel();

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/index.html'),
            $this->mockTokenManager(),
            $this->mockSession(),
            $this->getRootDir() . '/app',
            'contao_csrf_token'
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
        /** @var KernelInterface $kernel */
        global $kernel;

        $kernel = $this->mockKernel();

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/contao/install'),
            $this->mockTokenManager(),
            $this->mockSession(),
            $this->getRootDir() . '/app',
            'contao_csrf_token'
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
     * Tests that the Contao framework is initialized upon a sub request
     * if the master request is not within the scope.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFrontendSubRequest()
    {
        /** @var KernelInterface $kernel */
        global $kernel;

        $kernel = $this->mockKernel();

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/index.html'),
            $this->mockTokenManager(),
            $this->mockSession(),
            $this->getRootDir() . '/app',
            'contao_csrf_token'
        );

        $listener->setContainer($container);

        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertFalse(defined('TL_MODE'));
        $this->assertFalse(defined('TL_SCRIPT'));
        $this->assertFalse(defined('TL_ROOT'));

        $container->enterScope('frontend');

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST));

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertTrue(defined('TL_ROOT'));
    }

    /**
     * Tests a request without a scope.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWithoutScope()
    {
        /** @var KernelInterface $kernel */
        global $kernel;

        $kernel = $this->mockKernel();

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/index.html'),
            $this->mockTokenManager(),
            $this->mockSession(),
            $this->getRootDir() . '/app',
            'contao_csrf_token'
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
        /** @var KernelInterface $kernel */
        global $kernel;

        $kernel = $this->mockKernel();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/index.html'),
            $this->mockTokenManager(),
            $this->mockSession(),
            $this->getRootDir() . '/app',
            'contao_csrf_token'
        );

        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertFalse(defined('TL_MODE'));
        $this->assertFalse(defined('TL_SCRIPT'));
        $this->assertFalse(defined('TL_ROOT'));
    }

    /**
     * Tests that the Contao framework is not booted twice upon kernel.request.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testNotBootedTwiceUponKernelRequest()
    {
        /** @var KernelInterface $kernel */
        global $kernel;

        $kernel = $this->mockKernel();

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer();

        /** @var \PHPUnit_Framework_MockObject_MockObject|InitializeSystemListener $listener */
        $listener = $this->getMock(
            'Contao\\CoreBundle\\EventListener\\InitializeSystemListener',
            ['setConstants', 'boot'],
            [
                $this->mockRouter('/index.html'),
                $this->mockTokenManager(),
                $this->mockSession(),
                $this->getRootDir(),
                'contao_csrf_token'
            ]
        );

        $listener
            ->expects($this->once())
            ->method('setConstants')
        ;

        $listener
            ->expects($this->once())
            ->method('boot')
        ;

        $listener->setContainer($container);
        $container->enterScope('frontend');

        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));
        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST));
    }

    /**
     * Tests a console command.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testConsoleCommand()
    {
        /** @var KernelInterface $kernel */
        global $kernel;

        $kernel = $this->mockKernel();

        $listener = new InitializeSystemListener(
            $this->getMock('Symfony\\Component\\Routing\\RouterInterface'),
            $this->mockTokenManager(),
            $this->mockSession(),
            $this->getRootDir() . '/app',
            'contao_csrf_token'
        );

        $listener->onConsoleCommand(
            new ConsoleCommandEvent(new VersionCommand(), new StringInput(''), new ConsoleOutput())
        );

        $this->assertEquals('FE', TL_MODE);
        $this->assertEquals('console', TL_SCRIPT);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
    }

    /**
     * Tests that the Contao framework is not booted twice upon console.command.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testNotBootedTwiceUponConsoleCommand()
    {
        /** @var KernelInterface $kernel */
        global $kernel;

        $kernel = $this->mockKernel();

        /** @var \PHPUnit_Framework_MockObject_MockObject|InitializeSystemListener $listener */
        $listener = $this->getMock(
            'Contao\\CoreBundle\\EventListener\\InitializeSystemListener',
            ['setConstants', 'boot'],
            [
                $this->mockRouter('/index.html'),
                $this->mockTokenManager(),
                $this->mockSession(),
                $this->getRootDir(),
                'contao_csrf_token'
            ]
        );

        $listener
            ->expects($this->once())
            ->method('setConstants')
        ;

        $listener
            ->expects($this->once())
            ->method('boot')
        ;

        $listener->onConsoleCommand(
            new ConsoleCommandEvent(new VersionCommand(), new StringInput(''), new ConsoleOutput())
        );

        $listener->onConsoleCommand(
            new ConsoleCommandEvent(new VersionCommand(), new StringInput(''), new ConsoleOutput())
        );
    }

    /**
     * Mocks a router returning the given URL.
     *
     * @param string $url The URL to return
     *
     * @return RouterInterface The router object
     */
    protected function mockRouter($url)
    {
        $router = $this->getMock('Symfony\\Component\\Routing\\RouterInterface');

        $router
            ->expects($this->any())
            ->method('generate')
            ->willReturn($url)
        ;

        return $router;
    }
}
