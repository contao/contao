<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Framework;

use Contao\Config;
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
use Symfony\Component\Routing\RouterInterface;

class ContaoFrameworkTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $framework = $this->mockFramework(new RequestStack(), $this->mockRouter('/'));

        $this->assertInstanceOf('Contao\CoreBundle\Framework\ContaoFramework', $framework);
        $this->assertInstanceOf('Contao\CoreBundle\Framework\ContaoFrameworkInterface', $framework);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInitializesTheFrameworkWithAFrontEndRequest(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'frontend');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = $this->mockFramework($requestStack, $this->mockRouter('/index.html'));
        $framework->setContainer($this->mockContainer());
        $framework->initialize();

        $this->assertTrue(\defined('TL_MODE'));
        $this->assertTrue(\defined('TL_START'));
        $this->assertTrue(\defined('TL_ROOT'));
        $this->assertTrue(\defined('TL_REFERER_ID'));
        $this->assertTrue(\defined('TL_SCRIPT'));
        $this->assertFalse(\defined('BE_USER_LOGGED_IN'));
        $this->assertFalse(\defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('TL_PATH'));
        $this->assertSame('FE', TL_MODE);
        $this->assertSame($this->getTempDir(), TL_ROOT);
        $this->assertSame('', TL_REFERER_ID);
        $this->assertSame('index.html', TL_SCRIPT);
        $this->assertSame('', TL_PATH);
        $this->assertSame('en', $GLOBALS['TL_LANGUAGE']);
        $this->assertInstanceOf('Contao\CoreBundle\Session\Attribute\ArrayAttributeBag', $_SESSION['BE_DATA']);
        $this->assertInstanceOf('Contao\CoreBundle\Session\Attribute\ArrayAttributeBag', $_SESSION['FE_DATA']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInitializesTheFrameworkWithABackEndRequest(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'backend');
        $request->attributes->set('_contao_referer_id', 'foobar');
        $request->setLocale('de');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = $this->mockFramework($requestStack, $this->mockRouter('/contao/login'));
        $framework->setContainer($this->mockContainer());
        $framework->initialize();

        $this->assertTrue(\defined('TL_MODE'));
        $this->assertTrue(\defined('TL_START'));
        $this->assertTrue(\defined('TL_ROOT'));
        $this->assertTrue(\defined('TL_REFERER_ID'));
        $this->assertTrue(\defined('TL_SCRIPT'));
        $this->assertTrue(\defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('TL_PATH'));
        $this->assertSame('BE', TL_MODE);
        $this->assertSame($this->getTempDir(), TL_ROOT);
        $this->assertSame('foobar', TL_REFERER_ID);
        $this->assertSame('contao/login', TL_SCRIPT);
        $this->assertSame('', TL_PATH);
        $this->assertSame('de', $GLOBALS['TL_LANGUAGE']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInitializesTheFrameworkWithoutARequest(): void
    {
        $framework = $this->mockFramework(new RequestStack(), $this->mockRouter('/contao/login'));
        $framework->setContainer($this->mockContainer());
        $framework->initialize();

        $this->assertTrue(\defined('TL_MODE'));
        $this->assertTrue(\defined('TL_START'));
        $this->assertTrue(\defined('TL_ROOT'));
        $this->assertTrue(\defined('TL_REFERER_ID'));
        $this->assertTrue(\defined('TL_SCRIPT'));
        $this->assertTrue(\defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('TL_PATH'));
        $this->assertNull(TL_MODE);
        $this->assertSame($this->getTempDir(), TL_ROOT);
        $this->assertNull(TL_REFERER_ID);
        $this->assertSame(null, TL_SCRIPT);
        $this->assertNull(TL_PATH);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInitializesTheFrameworkWithoutARoute(): void
    {
        $request = new Request();
        $request->setLocale('de');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $routingLoader = $this->createMock(LoaderInterface::class);

        $routingLoader
            ->method('load')
            ->willReturn(new RouteCollection())
        ;

        $container = $this->mockContainer();
        $container->set('routing.loader', $routingLoader);

        $framework = $this->mockFramework($requestStack, new Router($container, []));
        $framework->setContainer($container);
        $framework->initialize();

        $this->assertTrue(\defined('TL_MODE'));
        $this->assertTrue(\defined('TL_START'));
        $this->assertTrue(\defined('TL_ROOT'));
        $this->assertTrue(\defined('TL_REFERER_ID'));
        $this->assertTrue(\defined('TL_SCRIPT'));
        $this->assertTrue(\defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('TL_PATH'));
        $this->assertSame(null, TL_MODE);
        $this->assertSame($this->getTempDir(), TL_ROOT);
        $this->assertSame('', TL_REFERER_ID);
        $this->assertSame(null, TL_SCRIPT);
        $this->assertSame('', TL_PATH);
        $this->assertSame('de', $GLOBALS['TL_LANGUAGE']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInitializesTheFrameworkWithoutAScope(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = $this->mockFramework($requestStack, $this->mockRouter('/contao/login'));
        $framework->setContainer($this->mockContainer());
        $framework->initialize();

        $this->assertTrue(\defined('TL_MODE'));
        $this->assertTrue(\defined('TL_START'));
        $this->assertTrue(\defined('TL_ROOT'));
        $this->assertTrue(\defined('TL_REFERER_ID'));
        $this->assertTrue(\defined('TL_SCRIPT'));
        $this->assertTrue(\defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('TL_PATH'));
        $this->assertNull(TL_MODE);
        $this->assertSame($this->getTempDir(), TL_ROOT);
        $this->assertSame('foobar', TL_REFERER_ID);
        $this->assertSame('contao/login', TL_SCRIPT);
        $this->assertSame('', TL_PATH);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoesNotInitializeTheFrameworkTwice(): void
    {
        $request = new Request();
        $request->attributes->set('_contao_referer_id', 'foobar');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = $this->mockContainer();
        $container->setParameter('contao.csrf_token_name', 'dummy_token');

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

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testOverridesTheErrorLevel(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = $this->mockFramework($requestStack, $this->mockRouter('/contao/login'));
        $framework->setContainer($this->mockContainer());

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
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testValidatesTheRequestToken(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_token_check', true);
        $request->setMethod('POST');
        $request->request->set('REQUEST_TOKEN', 'foobar');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = $this->mockFramework($requestStack, $this->mockRouter('/contao/login'));
        $framework->setContainer($this->mockContainer());
        $framework->initialize();

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFailsIfTheRequestTokenIsInvalid(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_token_check', true);
        $request->setMethod('POST');
        $request->request->set('REQUEST_TOKEN', 'invalid');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = new ContaoFramework(
            $requestStack,
            $this->mockRouter('/contao/login'),
            $this->mockSession(),
            $this->mockScopeMatcher(),
            $this->getTempDir(),
            error_reporting()
        );

        $framework->setContainer($this->mockContainer());

        $adapters = [
            Config::class => $this->mockConfigAdapter(),
            RequestToken::class => $this->mockRequestTokenAdapter(false),
        ];

        $ref = new \ReflectionObject($framework);
        $adapterCache = $ref->getProperty('adapterCache');
        $adapterCache->setAccessible(true);
        $adapterCache->setValue($framework, $adapters);

        $this->expectException(InvalidRequestTokenException::class);

        $framework->initialize();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoesNotValidateTheRequestTokenUponAjaxRequests(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_token_check', true);
        $request->setMethod('POST');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = new ContaoFramework(
            $requestStack,
            $this->mockRouter('/contao/login'),
            $this->mockSession(),
            $this->mockScopeMatcher(),
            $this->getTempDir(),
            error_reporting()
        );

        $framework->setContainer($this->mockContainer());

        $adapters = [
            Config::class => $this->mockConfigAdapter(),
            RequestToken::class => $this->mockRequestTokenAdapter(false),
        ];

        $ref = new \ReflectionObject($framework);
        $adapterCache = $ref->getProperty('adapterCache');
        $adapterCache->setAccessible(true);
        $adapterCache->setValue($framework, $adapters);

        $framework->initialize();

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoesNotValidateTheRequestTokenIfTheRequestAttributeIsFalse(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_token_check', false);
        $request->setMethod('POST');
        $request->request->set('REQUEST_TOKEN', 'foobar');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = new ContaoFramework(
            $requestStack,
            $this->mockRouter('/contao/login'),
            $this->mockSession(),
            $this->mockScopeMatcher(),
            $this->getTempDir(),
            error_reporting()
        );

        $framework->setContainer($this->mockContainer());

        $adapter = $this->mockAdapter(['get', 'validate']);

        $adapter
            ->method('get')
            ->willReturn('foobar')
        ;

        $adapter
            ->expects($this->never())
            ->method('validate')
        ;

        $adapters = [
            Config::class => $this->mockConfigAdapter(),
            RequestToken::class => $adapter,
        ];

        $ref = new \ReflectionObject($framework);
        $adapterCache = $ref->getProperty('adapterCache');
        $adapterCache->setAccessible(true);
        $adapterCache->setValue($framework, $adapters);

        $framework->initialize();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFailsIfTheInstallationIsIncomplete(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = new ContaoFramework(
            $requestStack,
            $this->mockRouter('/contao/login'),
            $this->mockSession(),
            $this->mockScopeMatcher(),
            $this->getTempDir(),
            error_reporting()
        );

        $framework->setContainer($this->mockContainer());

        $adapters = [
            Config::class => $this->mockConfigAdapter(false),
            RequestToken::class => $this->mockRequestTokenAdapter(),
        ];

        $ref = new \ReflectionObject($framework);
        $adapterCache = $ref->getProperty('adapterCache');
        $adapterCache->setAccessible(true);
        $adapterCache->setValue($framework, $adapters);

        $this->expectException(IncompleteInstallationException::class);

        $framework->initialize();
    }

    /**
     * @param string $route
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @dataProvider getInstallRoutes
     */
    public function testAllowsTheInstallationToBeIncompleteInTheInstallTool($route): void
    {
        $request = new Request();
        $request->attributes->set('_route', $route);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = new ContaoFramework(
            $requestStack,
            $this->mockRouter('/contao/login'),
            $this->mockSession(),
            $this->mockScopeMatcher(),
            $this->getTempDir(),
            error_reporting()
        );

        $framework->setContainer($this->mockContainer());

        $adapters = [
            Config::class => $this->mockConfigAdapter(false),
            RequestToken::class => $this->mockRequestTokenAdapter(),
        ];

        $ref = new \ReflectionObject($framework);
        $adapterCache = $ref->getProperty('adapterCache');
        $adapterCache->setAccessible(true);
        $adapterCache->setValue($framework, $adapters);

        $framework->initialize();

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    /**
     * @return array
     */
    public function getInstallRoutes(): array
    {
        return [
            'contao_install' => ['contao_install'],
            'contao_install_redirect' => ['contao_install_redirect'],
        ];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFailsIfTheContainerIsNotSet(): void
    {
        $framework = $this->mockFramework(new RequestStack(), $this->mockRouter('/contao/login'));

        $this->expectException('LogicException');

        $framework->initialize();
    }

    public function testCreatesAnObjectInstance(): void
    {
        $reflection = new \ReflectionClass(ContaoFramework::class);
        $framework = $reflection->newInstanceWithoutConstructor();

        $class = LegacyClass::class;
        $instance = $framework->createInstance($class, [1, 2]);

        $this->assertInstanceOf($class, $instance);
        $this->assertSame([1, 2], $instance->constructorArgs);
    }

    public function testCreateASingeltonObjectInstance(): void
    {
        $reflection = new \ReflectionClass(ContaoFramework::class);
        $framework = $reflection->newInstanceWithoutConstructor();

        $class = LegacySingletonClass::class;
        $instance = $framework->createInstance($class, [1, 2]);

        $this->assertInstanceOf($class, $instance);
        $this->assertSame([1, 2], $instance->constructorArgs);
    }

    public function testCreatesAdaptersForLegacyClasses(): void
    {
        $class = LegacyClass::class;

        $reflection = new \ReflectionClass(ContaoFramework::class);
        $framework = $reflection->newInstanceWithoutConstructor();
        $adapter = $framework->getAdapter($class);

        $this->assertInstanceOf('Contao\CoreBundle\Framework\Adapter', $adapter);

        $ref = new \ReflectionClass($adapter);
        $prop = $ref->getProperty('class');
        $prop->setAccessible(true);

        $this->assertSame($class, $prop->getValue($adapter));
    }

    public function testRegistersTheHookServices(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'backend');
        $request->attributes->set('_contao_referer_id', 'foobar');
        $request->setLocale('de');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = $this->mockContainer();
        $container->set('request_stack', $requestStack);
        $container->set('test.listener', new \stdClass());
        $container->set('test.listener2', new \stdClass());

        $GLOBALS['TL_HOOKS'] = [
            'getPageLayout' => [
                ['test.listener.c', 'onGetPageLayout'],
            ],
            'generatePage' => [
                ['test.listener.c', 'onGeneratePage'],
            ],
            'parseTemplate' => [
                ['test.listener.c', 'onParseTemplate'],
            ],
            'isVisibleElement' => [
                ['test.listener.c', 'onIsVisibleElement'],
            ],
        ];

        $listeners = [
            'getPageLayout' => [
                10 => [
                    ['test.listener.a', 'onGetPageLayout'],
                ],
                0 => [
                    ['test.listener.b', 'onGetPageLayout'],
                ],
            ],
            'generatePage' => [
                0 => [
                    ['test.listener.b', 'onGeneratePage'],
                ],
                -10 => [
                    ['test.listener.a', 'onGeneratePage'],
                ],
            ],
            'parseTemplate' => [
                10 => [
                    ['test.listener.a', 'onParseTemplate'],
                ],
            ],
            'isVisibleElement' => [
                -10 => [
                    ['test.listener.a', 'onIsVisibleElement'],
                ],
            ],
        ];

        $framework = $this->mockFramework($container->get('request_stack'), $this->mockRouter('/index.html'));
        $framework->setContainer($container);
        $framework->setHookListeners($listeners);

        $reflection = new \ReflectionObject($framework);
        $reflectionMethod = $reflection->getMethod('registerHookListeners');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($framework);

        $this->assertArrayHasKey('TL_HOOKS', $GLOBALS);
        $this->assertArrayHasKey('getPageLayout', $GLOBALS['TL_HOOKS']);
        $this->assertArrayHasKey('generatePage', $GLOBALS['TL_HOOKS']);
        $this->assertArrayHasKey('parseTemplate', $GLOBALS['TL_HOOKS']);
        $this->assertArrayHasKey('isVisibleElement', $GLOBALS['TL_HOOKS']);

        // Test hooks with high priority are added before low and legacy hooks
        // Test legacy hooks are added before hooks with priority 0
        $this->assertSame(
            [
                ['test.listener.a', 'onGetPageLayout'],
                ['test.listener.c', 'onGetPageLayout'],
                ['test.listener.b', 'onGetPageLayout'],
            ],
            $GLOBALS['TL_HOOKS']['getPageLayout']
        );

        // Test hooks with negative priority are added at the end
        $this->assertSame(
            [
                ['test.listener.c', 'onGeneratePage'],
                ['test.listener.b', 'onGeneratePage'],
                ['test.listener.a', 'onGeneratePage'],
            ],
            $GLOBALS['TL_HOOKS']['generatePage']
        );

        // Test legacy hooks are kept when adding only hook listeners with high priority.
        $this->assertSame(
            [
                ['test.listener.a', 'onParseTemplate'],
                ['test.listener.c', 'onParseTemplate'],
            ],
            $GLOBALS['TL_HOOKS']['parseTemplate']
        );

        // Test legacy hooks are kept when adding only hook listeners with low priority.
        $this->assertSame(
            [
                ['test.listener.c', 'onIsVisibleElement'],
                ['test.listener.a', 'onIsVisibleElement'],
            ],
            $GLOBALS['TL_HOOKS']['isVisibleElement']
        );
    }

    /**
     * Mocks a router.
     *
     * @param string $url
     *
     * @return RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockRouter(string $url): RouterInterface
    {
        $router = $this->createMock(RouterInterface::class);

        $router
            ->method('generate')
            ->willReturn($url)
        ;

        return $router;
    }

    /**
     * Mocks the Contao framework.
     *
     * @param RequestStack    $requestStack
     * @param RouterInterface $router
     *
     * @return ContaoFramework
     */
    private function mockFramework(RequestStack $requestStack, RouterInterface $router): ContaoFramework
    {
        $session = $this->mockSession();
        $session->start();

        $framework = new ContaoFramework(
            $requestStack,
            $router,
            $session,
            $this->mockScopeMatcher(),
            $this->getTempDir(),
            error_reporting()
        );

        $adapters = [
            Config::class => $this->mockConfigAdapter(),
            RequestToken::class => $this->mockRequestTokenAdapter(),
        ];

        $ref = new \ReflectionObject($framework);
        $adapterCache = $ref->getProperty('adapterCache');
        $adapterCache->setAccessible(true);
        $adapterCache->setValue($framework, $adapters);

        return $framework;
    }

    /**
     * Mocks a config adapter.
     *
     * @param bool $complete
     *
     * @return Adapter
     */
    private function mockConfigAdapter(bool $complete = true): Adapter
    {
        $config = $this->mockAdapter(['preload', 'isComplete', 'getInstance', 'get']);

        $config
            ->method('isComplete')
            ->willReturn($complete)
        ;

        $config
            ->method('getInstance')
            ->willReturn($config)
        ;

        $config
            ->method('get')
            ->willReturnCallback(
                function (string $key) {
                    if ('timeZone' === $key) {
                        return 'Europe/Berlin';
                    }

                    throw new \Exception(sprintf('Unknown key "%s"', $key));
                }
            )
        ;

        return $config;
    }

    /**
     * Mocks a request token adapter.
     *
     * @param bool $valid
     *
     * @return Adapter
     */
    private function mockRequestTokenAdapter(bool $valid = true): Adapter
    {
        $adapter = $this->mockAdapter(['get', 'validate']);

        $adapter
            ->method('get')
            ->willReturn('foobar')
        ;

        $adapter
            ->method('validate')
            ->willReturn($valid)
        ;

        return $adapter;
    }
}
