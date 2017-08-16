<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Framework;

use Contao\Config;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Exception\IncompleteInstallationException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\Fixtures\Adapter\LegacyClass;
use Contao\CoreBundle\Tests\Fixtures\Adapter\LegacySingletonClass;
use Contao\CoreBundle\Tests\TestCase;
use Contao\RequestToken;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

/**
 * Tests the ContaoFramework class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 * @author Yanick Witschi <https://github.com/toflar>
 * @author Dominik Tomasi <https://github.com/dtomasi>
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * @preserveGlobalState disabled
 */
class ContaoFrameworkTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $framework = $this->mockContaoFramework(
            new RequestStack(),
            $this->mockRouter('/')
        );

        $this->assertInstanceOf('Contao\CoreBundle\Framework\ContaoFramework', $framework);
        $this->assertInstanceOf('Contao\CoreBundle\Framework\ContaoFrameworkInterface', $framework);
    }

    /**
     * Tests initializing the framework with a front end request.
     *
     * @runInSeparateProcess
     */
    public function testFrontendRequest()
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $container = $this->mockContainerWithContaoScopes();
        $container->get('request_stack')->push($request);

        $framework = $this->mockContaoFramework($container->get('request_stack'), $this->mockRouter('/index.html'));
        $framework->setContainer($container);
        $framework->initialize();

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_START'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertTrue(defined('TL_REFERER_ID'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertFalse(defined('BE_USER_LOGGED_IN'));
        $this->assertFalse(defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(defined('TL_PATH'));
        $this->assertSame('FE', TL_MODE);
        $this->assertSame($this->getRootDir(), TL_ROOT);
        $this->assertSame('', TL_REFERER_ID);
        $this->assertSame('index.html', TL_SCRIPT);
        $this->assertSame('', TL_PATH);
        $this->assertSame('en', $GLOBALS['TL_LANGUAGE']);
        $this->assertInstanceOf('Contao\CoreBundle\Session\Attribute\ArrayAttributeBag', $_SESSION['BE_DATA']);
        $this->assertInstanceOf('Contao\CoreBundle\Session\Attribute\ArrayAttributeBag', $_SESSION['FE_DATA']);
    }

    /**
     * Tests initializing the framework with a back end request.
     *
     * @runInSeparateProcess
     */
    public function testBackendRequest()
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);
        $request->attributes->set('_contao_referer_id', 'foobar');
        $request->setLocale('de');

        $container = $this->mockContainerWithContaoScopes();
        $container->get('request_stack')->push($request);

        $framework = $this->mockContaoFramework($container->get('request_stack'), $this->mockRouter('/contao/login'));
        $framework->setContainer($container);
        $framework->initialize();

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_START'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertTrue(defined('TL_REFERER_ID'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertTrue(defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(defined('TL_PATH'));
        $this->assertSame('BE', TL_MODE);
        $this->assertSame($this->getRootDir(), TL_ROOT);
        $this->assertSame('foobar', TL_REFERER_ID);
        $this->assertSame('contao/login', TL_SCRIPT);
        $this->assertSame('', TL_PATH);
        $this->assertSame('de', $GLOBALS['TL_LANGUAGE']);
    }

    /**
     * Tests initializing the framework without a request.
     *
     * @runInSeparateProcess
     */
    public function testWithoutRequest()
    {
        $container = $this->mockContainerWithContaoScopes();
        $container->set('request_stack', new RequestStack());

        $framework = $this->mockContaoFramework($container->get('request_stack'), $this->mockRouter('/contao/login'));
        $framework->setContainer($container);
        $framework->initialize();

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_START'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertTrue(defined('TL_REFERER_ID'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertTrue(defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(defined('TL_PATH'));
        $this->assertNull(TL_MODE);
        $this->assertSame($this->getRootDir(), TL_ROOT);
        $this->assertNull(TL_REFERER_ID);
        $this->assertSame(null, TL_SCRIPT);
        $this->assertNull(TL_PATH);
    }

    /**
     * Tests initializing the framework with request but without route.
     *
     * @runInSeparateProcess
     */
    public function testWithoutRoute()
    {
        $request = new Request();
        $request->setLocale('de');

        $routingLoader = $this->createMock(LoaderInterface::class);

        $routingLoader
            ->method('load')
            ->willReturn(new RouteCollection())
        ;

        $container = $this->mockContainerWithContaoScopes();
        $container->get('request_stack')->push($request);
        $container->set('routing.loader', $routingLoader);

        $framework = $this->mockContaoFramework($container->get('request_stack'), new Router($container, []));
        $framework->setContainer($container);
        $framework->initialize();

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_START'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertTrue(defined('TL_REFERER_ID'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertTrue(defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(defined('TL_PATH'));
        $this->assertSame(null, TL_MODE);
        $this->assertSame($this->getRootDir(), TL_ROOT);
        $this->assertSame('', TL_REFERER_ID);
        $this->assertSame(null, TL_SCRIPT);
        $this->assertSame('', TL_PATH);
        $this->assertSame('de', $GLOBALS['TL_LANGUAGE']);
    }

    /**
     * Tests initializing the framework without a scope.
     *
     * @runInSeparateProcess
     */
    public function testWithoutScope()
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $container = $this->mockContainerWithContaoScopes();
        $container->get('request_stack')->push($request);

        $framework = $this->mockContaoFramework($container->get('request_stack'), $this->mockRouter('/contao/login'));
        $framework->setContainer($container);
        $framework->initialize();

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_START'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertTrue(defined('TL_REFERER_ID'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertTrue(defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(defined('TL_PATH'));
        $this->assertNull(TL_MODE);
        $this->assertSame($this->getRootDir(), TL_ROOT);
        $this->assertSame('foobar', TL_REFERER_ID);
        $this->assertSame('contao/login', TL_SCRIPT);
        $this->assertSame('', TL_PATH);
    }

    /**
     * Tests that the framework is not initialized twice.
     *
     * @runInSeparateProcess
     */
    public function testNotInitializedTwice()
    {
        $request = new Request();
        $request->attributes->set('_contao_referer_id', 'foobar');

        $container = $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);
        $container->setParameter('contao.csrf_token_name', 'dummy_token');
        $container->set('security.csrf.token_manager', new CsrfTokenManager());

        // Ensure to use the fixtures class
        Config::preload();

        $framework = $this->createMock(ContaoFramework::class);

        $framework
            ->method('isInitialized')
            ->willReturnOnConsecutiveCalls(false, true)
        ;

        $framework
            ->method('getAdapter')
            ->with($this->equalTo(Config::class))
            ->willReturn($this->mockConfigAdapter())
        ;

        $framework->setContainer($container);
        $framework->initialize();
        $framework->initialize();
    }

    /**
     * Tests that the error level will get updated when configured.
     *
     * @runInSeparateProcess
     */
    public function testErrorLevelOverride()
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $container = $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $framework = $this->mockContaoFramework($container->get('request_stack'), $this->mockRouter('/contao/login'));
        $framework->setContainer($container);

        $errorReporting = error_reporting();
        error_reporting(E_ALL ^ E_USER_NOTICE);

        $this->assertNotSame(
            $errorReporting,
            error_reporting(),
            'Test is invalid, error level has not changed.'
        );

        $framework->initialize();

        $this->assertSame($errorReporting, error_reporting());

        error_reporting($errorReporting);
    }

    /**
     * Tests initializing the framework with a valid request token.
     *
     * @runInSeparateProcess
     */
    public function testValidRequestToken()
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_token_check', true);
        $request->setMethod('POST');
        $request->request->set('REQUEST_TOKEN', 'foobar');

        $container = $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $framework = $this->mockContaoFramework(
            $container->get('request_stack'),
            $this->mockRouter('/contao/login')
        );

        $framework->setContainer($container);
        $framework->initialize();
    }

    /**
     * Tests initializing the framework with an invalid request token.
     *
     * @runInSeparateProcess
     */
    public function testInvalidRequestToken()
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_token_check', true);
        $request->setMethod('POST');
        $request->request->set('REQUEST_TOKEN', 'invalid');

        $container = $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $rtAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['validate'])
            ->getMock()
        ;

        $rtAdapter
            ->method('validate')
            ->willReturn(false)
        ;

        $framework = $this->mockContaoFramework(
            $container->get('request_stack'),
            null,
            [RequestToken::class => $rtAdapter]
        );

        $this->expectException(InvalidRequestTokenException::class);

        $framework->setContainer($container);
        $framework->initialize();
    }

    /**
     * Tests if the request token check is skipped if the attribute is false.
     *
     * @runInSeparateProcess
     */
    public function testRequestTokenCheckSkippedIfAttributeFalse()
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_token_check', false);
        $request->setMethod('POST');
        $request->request->set('REQUEST_TOKEN', 'foobar');

        $container = $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $rtAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'validate'])
            ->getMock()
        ;

        $rtAdapter
            ->method('get')
            ->willReturn('foobar')
        ;

        $rtAdapter
            ->expects($this->never())
            ->method('validate')
        ;

        $framework = $this->mockContaoFramework(
            $container->get('request_stack'),
            null,
            [RequestToken::class => $rtAdapter]
        );

        $framework->setContainer($container);
        $framework->initialize();
    }

    /**
     * Tests initializing the framework with an incomplete installation.
     *
     * @runInSeparateProcess
     */
    public function testIncompleteInstallation()
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $container = $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $configAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['isComplete', 'get', 'preload', 'getInstance'])
            ->getMock()
        ;

        $configAdapter
            ->method('isComplete')
            ->willReturn(false)
        ;

        $configAdapter
            ->method('get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'characterSet':
                        return 'UTF-8';

                    case 'timeZone':
                        return 'Europe/Berlin';

                    default:
                        return null;
                }
            })
        ;

        $framework = $this->mockContaoFramework(
            $container->get('request_stack'),
            $this->mockRouter('/contao/login'),
            [Config::class => $configAdapter]
        );

        $this->expectException(IncompleteInstallationException::class);

        $framework->setContainer($container);
        $framework->initialize();
    }

    /**
     * Tests initializing the framework with an incomplete installation on the install route.
     *
     * @runInSeparateProcess
     */
    public function testIncompleteInstallationOnInstallRoute()
    {
        $request = new Request();
        $request->attributes->set('_route', 'contao_install');

        $container = $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $configAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['isComplete', 'get', 'preload', 'getInstance'])
            ->getMock()
        ;

        $configAdapter
            ->method('isComplete')
            ->willReturn(false)
        ;

        $configAdapter
            ->method('get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'characterSet':
                        return 'UTF-8';

                    case 'timeZone':
                        return 'Europe/Berlin';

                    default:
                        return null;
                }
            })
        ;

        $framework = $this->mockContaoFramework(
            $container->get('request_stack'),
            $this->mockRouter('/contao/install'),
            [Config::class => $configAdapter]
        );

        $framework->setContainer($container);
        $framework->initialize();
    }

    /**
     * Tests initializing the framework with a valid request token.
     *
     * @runInSeparateProcess
     */
    public function testContainerNotSet()
    {
        $framework = $this->mockContaoFramework(
            new RequestStack(),
            $this->mockRouter('/contao/login')
        );

        $this->expectException('LogicException');

        $framework->setContainer();
        $framework->initialize();
    }

    /**
     * Tests the createInstance method.
     */
    public function testCreateInstance()
    {
        $reflection = new \ReflectionClass(ContaoFramework::class);
        $framework = $reflection->newInstanceWithoutConstructor();

        $class = LegacyClass::class;
        $instance = $framework->createInstance($class, [1, 2]);

        $this->assertInstanceOf($class, $instance);
        $this->assertSame([1, 2], $instance->constructorArgs);
    }

    /**
     * Tests the createInstance method for a singleton class.
     */
    public function testCreateInstanceSingelton()
    {
        $reflection = new \ReflectionClass(ContaoFramework::class);
        $framework = $reflection->newInstanceWithoutConstructor();

        $class = LegacySingletonClass::class;
        $instance = $framework->createInstance($class, [1, 2]);

        $this->assertInstanceOf($class, $instance);
        $this->assertSame([1, 2], $instance->constructorArgs);
    }

    /**
     * Tests the getAdapter method.
     */
    public function testGetAdapter()
    {
        $class = LegacyClass::class;

        $adapter = $this
            ->mockContaoFramework(
                null,
                null,
                [$class => new Adapter($class)]
            )
            ->getAdapter($class)
        ;

        $this->assertInstanceOf('Contao\CoreBundle\Framework\Adapter', $adapter);

        $ref = new \ReflectionClass($adapter);
        $prop = $ref->getProperty('class');
        $prop->setAccessible(true);

        $this->assertSame($class, $prop->getValue($adapter));
    }
}
