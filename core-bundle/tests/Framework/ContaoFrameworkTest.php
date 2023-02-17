<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Framework;

use Contao\Config;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Fixtures\Adapter\LegacyClass;
use Contao\CoreBundle\Fixtures\Adapter\LegacySingletonClass;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Contao\CoreBundle\Session\LazySessionAccess;
use Contao\CoreBundle\Session\MockNativeSessionStorage;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Environment;
use Contao\Input;
use Contao\Model\Registry;
use Contao\PageModel;
use Contao\RequestToken;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ResetInterface;

class ContaoFrameworkTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_HOOKS']);

        ini_restore('intl.default_locale');

        parent::tearDown();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInitializesTheFrameworkWithAFrontEndRequest(): void
    {
        $request = Request::create('/index.html');
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'frontend');

        $framework = $this->getFramework($request);
        $framework->setContainer($this->getContainerWithContaoConfiguration());
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
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInitializesTheFrameworkWithABackEndRequest(): void
    {
        $request = Request::create('/contao/login');
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'backend');
        $request->attributes->set('_contao_referer_id', 'foobar');
        $request->setLocale('de');

        $framework = $this->getFramework($request);
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize();

        $this->assertTrue(\defined('TL_MODE'));
        $this->assertTrue(\defined('TL_START'));
        $this->assertTrue(\defined('TL_ROOT'));
        $this->assertTrue(\defined('TL_REFERER_ID'));
        $this->assertTrue(\defined('TL_SCRIPT'));
        $this->assertFalse(\defined('BE_USER_LOGGED_IN'));
        $this->assertFalse(\defined('FE_USER_LOGGED_IN'));
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
        $framework = $this->getFramework();
        $framework->setContainer($this->getContainerWithContaoConfiguration());
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
        $this->assertNull(TL_SCRIPT);
        $this->assertFalse(BE_USER_LOGGED_IN);
        $this->assertFalse(FE_USER_LOGGED_IN);
        $this->assertNull(TL_PATH);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInitializesTheFrameworkWithoutARequestInFrontendMode(): void
    {
        $framework = $this->getFramework();
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize(true);

        $this->assertTrue(\defined('TL_MODE'));
        $this->assertTrue(\defined('TL_START'));
        $this->assertTrue(\defined('TL_ROOT'));
        $this->assertTrue(\defined('TL_REFERER_ID'));
        $this->assertTrue(\defined('TL_SCRIPT'));
        $this->assertTrue(\defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('TL_PATH'));
        $this->assertSame('FE', TL_MODE);
        $this->assertSame($this->getTempDir(), TL_ROOT);
        $this->assertNull(TL_REFERER_ID);
        $this->assertNull(TL_SCRIPT);
        $this->assertFalse(BE_USER_LOGGED_IN);
        $this->assertFalse(FE_USER_LOGGED_IN);
        $this->assertNull(TL_PATH);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInitializesTheFrameworkWithAnInsecurePath(): void
    {
        $request = Request::create('/contao4/public/index.php/index.html');
        $request->server->set('SCRIPT_FILENAME', '/var/www/contao4/public/index.php');
        $request->server->set('SCRIPT_NAME', '/contao4/public/index.php');

        $framework = $this->getFramework($request);
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize(true);

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
        $this->assertSame('index.php/index.html', TL_SCRIPT);
        $this->assertSame('/contao4/public', TL_PATH);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInitializesTheFrameworkWithoutAScope(): void
    {
        $request = Request::create('/contao/login');
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $framework = $this->getFramework($request);
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize();

        $this->assertTrue(\defined('TL_MODE'));
        $this->assertTrue(\defined('TL_START'));
        $this->assertTrue(\defined('TL_ROOT'));
        $this->assertTrue(\defined('TL_REFERER_ID'));
        $this->assertTrue(\defined('TL_SCRIPT'));
        $this->assertFalse(\defined('BE_USER_LOGGED_IN'));
        $this->assertFalse(\defined('FE_USER_LOGGED_IN'));
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
    public function testDoesNotSetTheLoginConstantsOnInit(): void
    {
        $request = Request::create('/index.html');
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'frontend');

        $framework = $this->getFramework($request);
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize();

        $this->assertFalse(\defined('BE_USER_LOGGED_IN'));
        $this->assertFalse(\defined('FE_USER_LOGGED_IN'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetsTheLoginConstantsOnInitIfEnabled(): void
    {
        $request = Request::create('/index.html');
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'frontend');

        $framework = $this->getFramework($request);
        $framework->setContainer($this->getContainerWithContaoConfiguration());

        // Call setLoginConstants before initialize
        $framework->setLoginConstants();
        $framework->initialize();

        $this->assertTrue(\defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('FE_USER_LOGGED_IN'));
        $this->assertFalse(BE_USER_LOGGED_IN);
        $this->assertFalse(FE_USER_LOGGED_IN);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetsTheLoginConstantsOnInitIfThereIsNoRequest(): void
    {
        $framework = $this->getFramework();
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize();

        $this->assertTrue(\defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('FE_USER_LOGGED_IN'));
        $this->assertFalse(BE_USER_LOGGED_IN);
        $this->assertFalse(FE_USER_LOGGED_IN);
    }

    /**
     * @group legacy
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInitializesTheFrameworkInPreviewMode(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.5: Using "$_SESSION" has been deprecated and will no longer work in Contao 5.0. Use the Symfony session instead.');

        $beBag = new ArrayAttributeBag();
        $beBag->setName('contao_backend');

        $feBag = new ArrayAttributeBag();
        $feBag->setName('contao_frontend');

        $session = new Session(new MockNativeSessionStorage());
        $session->registerBag($beBag);
        $session->registerBag($feBag);

        $request = Request::create('index.html');
        $request->server->set('SCRIPT_NAME', '/preview.php');
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'frontend');
        $request->cookies->set($session->getName(), 'foobar');
        $request->setSession($session);

        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->expects($this->once())
            ->method('isPreviewMode')
            ->willReturn(true)
        ;

        $tokenChecker
            ->expects($this->once())
            ->method('hasFrontendUser')
            ->willReturn(true)
        ;

        $framework = $this->getFramework($request, null, $tokenChecker);
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize();
        $framework->setLoginConstants();

        $this->assertTrue(\defined('TL_MODE'));
        $this->assertTrue(\defined('TL_START'));
        $this->assertTrue(\defined('TL_ROOT'));
        $this->assertTrue(\defined('TL_REFERER_ID'));
        $this->assertTrue(\defined('TL_SCRIPT'));
        $this->assertTrue(\defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(\defined('TL_PATH'));
        $this->assertSame('FE', TL_MODE);
        $this->assertSame($this->getTempDir(), TL_ROOT);
        $this->assertSame('', TL_REFERER_ID);
        $this->assertSame('index.html', TL_SCRIPT);
        $this->assertTrue(BE_USER_LOGGED_IN);
        $this->assertTrue(FE_USER_LOGGED_IN);
        $this->assertSame('', TL_PATH);
        $this->assertSame('en', $GLOBALS['TL_LANGUAGE']);
        $this->assertInstanceOf(ArrayAttributeBag::class, $_SESSION['BE_DATA']);
        $this->assertInstanceOf(ArrayAttributeBag::class, $_SESSION['FE_DATA']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoesNotInitializeTheFrameworkTwice(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->exactly(1))
            ->method('isBackendRequest')
            ->willReturn(false)
        ;

        $scopeMatcher
            ->expects($this->exactly(1))
            ->method('isFrontendRequest')
            ->willReturn(false)
        ;

        $framework = $this->getFramework(Request::create('/index.html'), $scopeMatcher);
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize();

        $this->assertTrue(\defined('TL_MODE'));
        $this->assertNull(TL_MODE);

        $framework->initialize();

        $this->addToAssertionCount(1); // does not throw an exception
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testOverridesTheErrorLevel(): void
    {
        $request = Request::create('/contao/login');
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $framework = $this->getFramework($request);
        $framework->setContainer($this->getContainerWithContaoConfiguration());

        $errorReporting = error_reporting();
        error_reporting(E_ALL ^ E_USER_NOTICE);

        $this->assertNotSame($errorReporting, error_reporting(), 'Test is invalid, error level has not changed.');

        $framework->initialize();

        $this->assertSame($errorReporting, error_reporting());

        error_reporting($errorReporting);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRedirectsToTheInstallToolIfTheInstallationIsIncomplete(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('contao_install', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('/contao/install')
        ;

        $request = Request::create('/contao/login');
        $request->attributes->set('_route', 'dummy');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = new ContaoFramework(
            $requestStack,
            $this->mockScopeMatcher(),
            $this->createMock(TokenChecker::class),
            new Filesystem(),
            $urlGenerator,
            $this->getTempDir(),
            error_reporting(),
            false
        );

        $framework->setContainer($this->getContainerWithContaoConfiguration());

        $adapters = [
            Config::class => $this->mockConfigAdapter(false),
            RequestToken::class => $this->mockRequestTokenAdapter(),
        ];

        $ref = new \ReflectionObject($framework);
        $adapterCache = $ref->getProperty('adapterCache');
        $adapterCache->setAccessible(true);
        $adapterCache->setValue($framework, $adapters);

        $this->expectException(RedirectResponseException::class);

        $framework->initialize();
    }

    /**
     * @dataProvider getInstallRoutes
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAllowsTheInstallationToBeIncompleteInTheInstallTool(string $route): void
    {
        $request = Request::create('/contao/login');
        $request->attributes->set('_route', $route);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = new ContaoFramework(
            $requestStack,
            $this->mockScopeMatcher(),
            $this->createMock(TokenChecker::class),
            new Filesystem(),
            $this->createMock(UrlGeneratorInterface::class),
            $this->getTempDir(),
            error_reporting(),
            false
        );

        $framework->setContainer($this->getContainerWithContaoConfiguration());

        $adapters = [
            Config::class => $this->mockConfigAdapter(false),
            RequestToken::class => $this->mockRequestTokenAdapter(),
        ];

        $ref = new \ReflectionObject($framework);
        $adapterCache = $ref->getProperty('adapterCache');
        $adapterCache->setAccessible(true);
        $adapterCache->setValue($framework, $adapters);

        $framework->initialize();

        $this->addToAssertionCount(1); // does not throw an exception
    }

    public function getInstallRoutes(): \Generator
    {
        yield 'contao_install' => ['contao_install'];
        yield 'contao_install_redirect' => ['contao_install_redirect'];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFailsIfTheContainerIsNotSet(): void
    {
        $framework = $this->getFramework();

        $this->expectException('LogicException');

        $framework->initialize();
    }

    /**
     * @group legacy
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRegistersTheLazySessionAccessObject(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.5: Using "$_SESSION" has been deprecated %s.');

        $beBag = new ArrayAttributeBag();
        $beBag->setName('contao_backend');

        $feBag = new ArrayAttributeBag();
        $feBag->setName('contao_frontend');

        $session = new Session(new MockNativeSessionStorage());
        $session->registerBag($beBag);
        $session->registerBag($feBag);

        $request = Request::create('/index.html');
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'frontend');
        $request->setSession($session);

        $framework = $this->getFramework($request);
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize();

        /** @phpstan-ignore-next-line */
        $this->assertInstanceOf(LazySessionAccess::class, $_SESSION);
        $this->assertInstanceOf(ArrayAttributeBag::class, $_SESSION['BE_DATA']);
        $this->assertInstanceOf(ArrayAttributeBag::class, $_SESSION['FE_DATA']);
    }

    public function testCreatesAnObjectInstance(): void
    {
        $framework = $this->getFramework();

        $class = LegacyClass::class;
        $instance = $framework->createInstance($class, [1, 2]);

        $this->assertInstanceOf($class, $instance);
        $this->assertSame([1, 2], $instance->constructorArgs);
    }

    public function testCreateASingeltonObjectInstance(): void
    {
        $framework = $this->getFramework();

        $class = LegacySingletonClass::class;
        $instance = $framework->createInstance($class, [1, 2]);

        $this->assertInstanceOf($class, $instance);
        $this->assertSame([1, 2], $instance->constructorArgs);
    }

    public function testCreatesAdaptersForLegacyClasses(): void
    {
        $framework = $this->getFramework();
        $adapter = $framework->getAdapter(LegacyClass::class);

        $ref = new \ReflectionClass($adapter);
        $prop = $ref->getProperty('class');
        $prop->setAccessible(true);

        $this->assertSame(LegacyClass::class, $prop->getValue($adapter));
    }

    public function testRegistersTheHookServices(): void
    {
        $request = Request::create('/index.html');
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'backend');
        $request->attributes->set('_contao_referer_id', 'foobar');
        $request->setLocale('de');

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('test.listener', new \stdClass());
        $container->set('test.listener2', new \stdClass());

        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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

        $framework = $this->getFramework($request);
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

    public function testServiceIsResetable(): void
    {
        $this->assertInstanceOf(ResetInterface::class, $this->getFramework());

        $framework = $this->getFramework();
        $adapter = $framework->getAdapter(Input::class);

        $this->assertSame($adapter, $framework->getAdapter(Input::class));

        $framework->reset();

        $this->assertNotSame($adapter, $framework->getAdapter(Input::class));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDelegatesTheResetCalls(): void
    {
        $framework = $this->getFramework();
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize();

        Environment::set('scriptFilename', 'bar');
        Input::setUnusedGet('foo', 'bar');

        $model = $this
            ->getMockBuilder(PageModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['onRegister'])
            ->getMock()
        ;

        $model->id = 1;

        $registry = Registry::getInstance();
        $registry->register($model);

        $this->assertSame('bar', Environment::get('scriptFilename'));
        $this->assertNotEmpty(Input::getUnusedGet());
        $this->assertCount(1, $registry);

        $framework->reset();

        $this->assertNotSame('bar', Environment::get('scriptFilename'));
        $this->assertEmpty(Input::getUnusedGet());
        $this->assertCount(0, $registry);
    }

    private function getFramework(Request $request = null, ScopeMatcher $scopeMatcher = null, TokenChecker $tokenChecker = null): ContaoFramework
    {
        $requestStack = new RequestStack();

        if (null !== $request) {
            $requestStack->push($request);
        }

        $framework = new ContaoFramework(
            $requestStack,
            $scopeMatcher ?? $this->mockScopeMatcher(),
            $tokenChecker ?? $this->createMock(TokenChecker::class),
            new Filesystem(),
            $this->createMock(UrlGeneratorInterface::class),
            $this->getTempDir(),
            error_reporting(),
            false
        );

        $adapters = [
            Config::class => $this->mockConfigAdapter(),
            RequestToken::class => $this->mockRequestTokenAdapter(),
        ];

        $ref = new \ReflectionObject($framework);
        $adapterCache = $ref->getProperty('adapterCache');
        $adapterCache->setAccessible(true);
        $adapterCache->setValue($framework, $adapters);

        $isInitialized = $ref->getProperty('initialized');
        $isInitialized->setAccessible(true);
        $isInitialized->setValue(false);

        return $framework;
    }

    /**
     * @return Adapter<Config>&MockObject
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
            ->with('timeZone')
            ->willReturn('Europe/Berlin')
        ;

        return $config;
    }

    /**
     * @return Adapter<RequestToken>&MockObject
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
