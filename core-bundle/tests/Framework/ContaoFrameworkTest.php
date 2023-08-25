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
use Contao\CoreBundle\Fixtures\Adapter\LegacyClass;
use Contao\CoreBundle\Fixtures\Adapter\LegacySingletonClass;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Environment;
use Contao\Input;
use Contao\Model\Registry;
use Contao\PageModel;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\Service\ResetInterface;

class ContaoFrameworkTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_HOOKS'], $GLOBALS['TL_LANGUAGE']);

        $this->resetStaticProperties([System::class, ContaoFramework::class, Registry::class]);

        ini_restore('intl.default_locale');

        parent::tearDown();
    }

    public function testInitializesTheFrameworkWithAFrontEndRequest(): void
    {
        $request = Request::create('/index.html');
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'frontend');

        $framework = $this->getFramework($request);
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize();

        $this->assertSame('en', $GLOBALS['TL_LANGUAGE']);
    }

    public function testInitializesTheFrameworkWithABackEndRequest(): void
    {
        $request = Request::create('/contao/login');
        $request->setLocale('de');

        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'backend');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $framework = $this->getFramework($request);
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize();

        $this->assertSame('de', $GLOBALS['TL_LANGUAGE']);
    }

    public function testInitializesTheFrameworkWithoutARequest(): void
    {
        $framework = $this->getFramework();
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize();
    }

    public function testInitializesTheFrameworkWithoutARequestInFrontendMode(): void
    {
        $framework = $this->getFramework();
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize();
    }

    public function testInitializesTheFrameworkWithAnInsecurePath(): void
    {
        $request = Request::create('/contao4/public/index.php/index.html');
        $request->server->set('SCRIPT_FILENAME', '/var/www/contao4/public/index.php');
        $request->server->set('SCRIPT_NAME', '/contao4/public/index.php');

        $framework = $this->getFramework($request);
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize();
    }

    public function testInitializesTheFrameworkWithoutAScope(): void
    {
        $request = Request::create('/contao/login');
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $framework = $this->getFramework($request);
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize();
    }

    public function testInitializesTheFrameworkInPreviewMode(): void
    {
        $beBag = new ArrayAttributeBag();
        $beBag->setName('contao_backend');

        $feBag = new ArrayAttributeBag();
        $feBag->setName('contao_frontend');

        $session = new Session(new MockArraySessionStorage());
        $session->registerBag($beBag);
        $session->registerBag($feBag);

        $request = Request::create('index.html');
        $request->setSession($session);

        $request->server->set('SCRIPT_NAME', '/preview.php');
        $request->cookies->set($session->getName(), 'foobar');

        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'frontend');

        $framework = $this->getFramework($request);
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize();

        $this->assertSame('en', $GLOBALS['TL_LANGUAGE']);
    }

    public function testDoesNotInitializeTheFrameworkTwice(): void
    {
        $framework = $this->getFramework(Request::create('/index.html'));
        $framework->setContainer($this->getContainerWithContaoConfiguration());
        $framework->initialize();
        $framework->initialize();

        $this->addToAssertionCount(1); // does not throw an exception
    }

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

    public function testFailsIfTheContainerIsNotSet(): void
    {
        $framework = $this->getFramework();

        $this->expectException('LogicException');

        $framework->initialize();
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

        $this->assertSame(LegacyClass::class, $prop->getValue($adapter));
    }

    public function testRegistersTheHookServices(): void
    {
        $request = Request::create('/index.html');
        $request->setLocale('de');

        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', 'backend');
        $request->attributes->set('_contao_referer_id', 'foobar');

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
        $reflectionMethod->invoke($framework);

        $this->assertArrayHasKey('TL_HOOKS', $GLOBALS);
        $this->assertArrayHasKey('getPageLayout', $GLOBALS['TL_HOOKS']);
        $this->assertArrayHasKey('generatePage', $GLOBALS['TL_HOOKS']);
        $this->assertArrayHasKey('parseTemplate', $GLOBALS['TL_HOOKS']);
        $this->assertArrayHasKey('isVisibleElement', $GLOBALS['TL_HOOKS']);

        $getPageLayout = $GLOBALS['TL_HOOKS']['getPageLayout'];
        $generatePage = $GLOBALS['TL_HOOKS']['generatePage'];
        $parseTemplate = $GLOBALS['TL_HOOKS']['parseTemplate'];
        $isVisibleElement = $GLOBALS['TL_HOOKS']['isVisibleElement'];

        // Test hooks with high priority are added before low and legacy hooks
        // Test legacy hooks are added before hooks with priority 0
        $this->assertSame(
            [
                ['test.listener.a', 'onGetPageLayout'],
                ['test.listener.c', 'onGetPageLayout'],
                ['test.listener.b', 'onGetPageLayout'],
            ],
            $getPageLayout,
        );

        // Test hooks with negative priority are added at the end
        $this->assertSame(
            [
                ['test.listener.c', 'onGeneratePage'],
                ['test.listener.b', 'onGeneratePage'],
                ['test.listener.a', 'onGeneratePage'],
            ],
            $generatePage,
        );

        // Test legacy hooks are kept when adding only hook listeners with high priority.
        $this->assertSame(
            [
                ['test.listener.a', 'onParseTemplate'],
                ['test.listener.c', 'onParseTemplate'],
            ],
            $parseTemplate,
        );

        // Test legacy hooks are kept when adding only hook listeners with low priority.
        $this->assertSame(
            [
                ['test.listener.c', 'onIsVisibleElement'],
                ['test.listener.a', 'onIsVisibleElement'],
            ],
            $isVisibleElement,
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

    public function testDelegatesTheResetCalls(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            // Backwards compatibility with doctrine/dbal < 3.5
            ->method(method_exists($schemaManager, 'introspectSchema') ? 'introspectSchema' : 'createSchema')
            ->willReturn(new Schema())
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('database_connection', $connection);

        $framework = $this->getFramework();
        $framework->setContainer($container);
        $framework->initialize();

        Environment::set('scriptFilename', 'bar');
        Input::setUnusedRouteParameters(['foo']);

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
        $this->assertNotEmpty(Input::getUnusedRouteParameters());
        $this->assertCount(1, $registry);

        $framework->reset();

        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/index.html'));

        $container = new ContainerBuilder();
        $container->set('request_stack', $requestStack);

        System::setContainer($container);

        $this->assertNotSame('bar', Environment::get('scriptFilename'));
        $this->assertEmpty(Input::getUnusedRouteParameters());
        $this->assertCount(0, $registry);
    }

    private function getFramework(Request|null $request = null): ContaoFramework
    {
        $requestStack = new RequestStack();

        if ($request) {
            $requestStack->push($request);
        }

        $framework = new ContaoFramework(
            $requestStack,
            $this->getTempDir(),
            error_reporting(),
        );

        $adapters = [
            Config::class => $this->mockConfigAdapter(),
        ];

        $ref = new \ReflectionObject($framework);
        $adapterCache = $ref->getProperty('adapterCache');
        $adapterCache->setValue($framework, $adapters);

        $isInitialized = $ref->getProperty('initialized');
        $isInitialized->setValue(null, false);

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
            ->willReturn(\ini_get('date.timezone'))
        ;

        return $config;
    }
}
