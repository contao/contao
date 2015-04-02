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
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\EventListener\InitializeSystemListener;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

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
            $this->mockSession(),
            $this->getRootDir(),
            $this->getMock('Symfony\\Component\\Security\\Csrf\\CsrfTokenManagerInterface'),
            'contao_csrf_token',
            $this->mockConfig()

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
        Config::preload();

        /** @var KernelInterface $kernel */
        global $kernel;

        $kernel = $this->mockKernel();

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/index.html'),
            $this->mockSession(),
            $this->getRootDir() . '/app',
            $this->mockTokenManager(),
            'contao_csrf_token',
            $this->mockConfig()
        );

        $listener->setContainer($container);

        $container->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);

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
        Config::preload();

        /** @var KernelInterface $kernel */
        global $kernel;

        $kernel = $this->mockKernel();

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/contao/install'),
            $this->mockSession(),
            $this->getRootDir() . '/app',
            $this->mockTokenManager(),
            'contao_csrf_token',
            $this->mockConfig()
        );

        $listener->setContainer($container);

        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);

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
        Config::preload();

        /** @var KernelInterface $kernel */
        global $kernel;

        $kernel = $this->mockKernel();

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/index.html'),
            $this->mockSession(),
            $this->getRootDir() . '/app',
            $this->mockTokenManager(),
            'contao_csrf_token',
            $this->mockConfig()
        );

        $listener->setContainer($container);

        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertFalse(defined('TL_MODE'));
        $this->assertFalse(defined('TL_SCRIPT'));
        $this->assertFalse(defined('TL_ROOT'));

        $container->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);

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
            $this->mockSession(),
            $this->getRootDir() . '/app',
            $this->mockTokenManager(),
            'contao_csrf_token',
            $this->mockConfig()
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
            $this->mockSession(),
            $this->getRootDir() . '/app',
            $this->mockTokenManager(),
            'contao_csrf_token',
            $this->mockConfig()
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
                $this->mockSession(),
                $this->getRootDir(),
                $this->mockTokenManager(),
                'contao_csrf_token',
                $this->mockConfig()
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
        $container->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);

        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));
        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST));
    }

    /**
     * Tests a request without scope.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @expectedException \Contao\CoreBundle\Exception\InsecureInstallationHttpException
     */
    public function testValidateInstallation()
    {
        global $kernel;

        $kernel   = $this->mockKernel();
        $listener = new InitializeSystemListener(
            $this->mockRouter('/web/app_dev.php?do=test'),
            $this->mockSession(),
            $this->getRootDir() . '/app',
            $this->mockTokenManager(),
            'contao_csrf_token'
        );
        $listener->setContainer($kernel->getContainer());

        $request = new Request();
        $request->server->add([
            'SERVER_PORT'          => 80,
            'HTTP_HOST'            => 'localhost',
            'HTTP_CONNECTION'      => 'close',
            'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'HTTP_USER_AGENT'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.149 Safari/537.36',
            'HTTP_ACCEPT_ENCODING' => 'gzip,deflate,sdch',
            'HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.8,en-GB;q=0.6,en;q=0.4',
            'HTTP_X_FORWARDED_FOR' => '123.456.789.0',
            'SERVER_NAME'          => 'localhost',
            'SERVER_ADDR'          => '127.0.0.1',
            'REMOTE_ADDR'          => '123.456.789.0',
            'DOCUMENT_ROOT'        => $this->getRootDir(),
            'SCRIPT_FILENAME'      => $this->getRootDir() . '/foo/web/app_dev.php',
            'ORIG_SCRIPT_FILENAME' => '/var/run/localhost.fcgi',
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'QUERY_STRING'         => 'do=test',
            'REQUEST_URI'          => '/web/app_dev.php?do=test',
            'SCRIPT_NAME'          => '/foo/web/app_dev.php',
            'ORIG_SCRIPT_NAME'     => '/php.fcgi',
            'PHP_SELF'             => '/foo/web/app_dev.php',
            'GATEWAY_INTERFACE'    => 'CGI/1.1',
            'ORIG_PATH_INFO'       => '/foo/web/app_dev.php',
            'ORIG_PATH_TRANSLATED' => $this->getRootDir() . '/foo/web/app_dev.php',
        ]);
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'backend');
        $kernel->getContainer()->enterScope('backend');

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener->onKernelRequest($event);
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
            $this->mockSession(),
            $this->getRootDir() . '/app',
            $this->mockTokenManager(),
            'contao_csrf_token',
            $this->mockConfig()
        );
        $listener->setContainer($kernel->getContainer());

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
                $this->mockSession(),
                $this->getRootDir(),
                $this->mockTokenManager(),
                'contao_csrf_token',
                $this->mockConfig(),
            ]
        );
        $listener->setContainer($kernel->getContainer());

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
     * Tests that the error level will get updated when configured.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorLevelOverride()
    {
        /** @var KernelInterface $kernel */
        global $kernel;

        $kernel = $this->mockKernel();

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer();

        $listener = new InitializeSystemListener(
            $this->mockRouter('/contao/install'),
            $this->mockSession(),
            $this->getRootDir() . '/app',
            $this->mockTokenManager(),
            'contao_csrf_token'
        );

        $listener->setContainer($container);

        $container->enterScope('backend');

        $request = new Request();
        $request->attributes->set('_route', 'dummy');


        $keeper = error_reporting();
        $kernel->getContainer()->setParameter('contao.error_level', E_ALL ^ (E_NOTICE | E_STRICT | E_DEPRECATED));
        error_reporting(E_ALL);
        $this->assertNotEquals(
            $kernel->getContainer()->getParameter('contao.error_level'),
            error_reporting(),
            'Test is invalid, error level is not changed.'
        );

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));
        $this->assertEquals($kernel->getContainer()->getParameter('contao.error_level'), error_reporting());

        // Restore error reporting.
        error_reporting($keeper);
    }
}
