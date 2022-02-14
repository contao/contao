<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Asset;

use Contao\Config;
use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\Model\Registry;
use Contao\PageModel;
use Contao\System;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContaoContextTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_MIME']);

        $this->resetStaticProperties([DcaExtractor::class, DcaLoader::class, Registry::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testReturnsAnEmptyBasePathInDebugMode(): void
    {
        $context = new ContaoContext(new RequestStack(), $this->mockContaoFramework(), 'staticPlugins', true);

        $this->assertSame('', $context->getBasePath());
    }

    public function testReturnsAnEmptyBasePathIfThereIsNoRequest(): void
    {
        $context = $this->getContaoContext('staticPlugins');

        $this->assertSame('', $context->getBasePath());
    }

    public function testReturnsTheBasePathIfThePageDoesNotDefineIt(): void
    {
        $page = $this->getPageWithDetails();

        $GLOBALS['objPage'] = $page;

        $request = Request::create(
            'https://example.com/foobar/index.php',
            'GET',
            [],
            [],
            [],
            [
                'SCRIPT_FILENAME' => '/foobar/index.php',
                'SCRIPT_NAME' => '/foobar/index.php',
            ]
        );

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $context = $this->getContaoContext('staticPlugins', $requestStack);

        $this->assertSame('/foobar', $context->getBasePath());

        unset($GLOBALS['objPage']);
    }

    /**
     * @dataProvider getBasePaths
     */
    public function testReadsTheBasePathFromThePageModel(string $domain, bool $useSSL, string $basePath, string $expected): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getBasePath')
            ->willReturn($basePath)
        ;

        $request->attributes = $this->createMock(ParameterBag::class);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $page = $this->getPageWithDetails();
        $page->rootUseSSL = $useSSL;
        $page->staticPlugins = $domain;

        $GLOBALS['objPage'] = $page;

        $context = $this->getContaoContext('staticPlugins', $requestStack);

        $this->assertSame($expected, $context->getBasePath());

        unset($GLOBALS['objPage']);
    }

    /**
     * @dataProvider getBasePaths
     */
    public function testUsesThePageModelFromRequestAttributes(string $domain, bool $useSSL, string $basePath, string $expected): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getBasePath')
            ->willReturn($basePath)
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $page = $this->getPageWithDetails();
        $page->rootUseSSL = $useSSL;
        $page->staticPlugins = $domain;

        $request->attributes = new ParameterBag(['pageModel' => $page]);
        unset($GLOBALS['objPage']);

        $context = $this->getContaoContext('staticPlugins', $requestStack);

        $this->assertSame($expected, $context->getBasePath());
    }

    /**
     * @dataProvider getBasePaths
     */
    public function testGetsPageModelFromIdInRequestAttributes(string $domain, bool $useSSL, string $basePath, string $expected): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getBasePath')
            ->willReturn($basePath)
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $page = $this->getPageWithDetails();
        $page->id = 42;
        $page->rootUseSSL = $useSSL;
        $page->staticPlugins = $domain;

        $request->attributes = new ParameterBag(['pageModel' => 42]);
        unset($GLOBALS['objPage']);

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->atLeastOnce())
            ->method('findByPk')
            ->with(42)
            ->willReturn($page)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $framework
            ->expects($this->atLeastOnce())
            ->method('initialize')
        ;

        $context = new ContaoContext($requestStack, $framework, 'staticPlugins');

        $this->assertSame($expected, $context->getBasePath());
    }

    /**
     * @dataProvider getBasePaths
     */
    public function testUsesTheGlobalPageModelWithSameIdInRequestAttributes(string $domain, bool $useSSL, string $basePath, string $expected): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getBasePath')
            ->willReturn($basePath)
        ;

        $request->attributes = new ParameterBag(['pageModel' => 42]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $page = $this->getPageWithDetails();
        $page->id = 42;
        $page->rootUseSSL = $useSSL;
        $page->staticPlugins = $domain;

        $GLOBALS['objPage'] = $page;

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->never())
            ->method('findByPk')
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $context = new ContaoContext($requestStack, $framework, 'staticPlugins');

        $this->assertSame($expected, $context->getBasePath());

        unset($GLOBALS['objPage']);
    }

    public function getBasePaths(): \Generator
    {
        yield ['example.com', true, '', 'https://example.com'];
        yield ['example.com', false, '', 'http://example.com'];
        yield ['example.com', true, '/foo', 'https://example.com/foo'];
        yield ['example.com', false, '/foo', 'http://example.com/foo'];
        yield ['example.ch', false, '/bar', 'http://example.ch/bar'];
    }

    public function testReturnsTheStaticUrl(): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getBasePath')
            ->willReturn('/foo')
        ;

        $request->attributes = $this->createMock(ParameterBag::class);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $page = $this->getPageWithDetails();
        $page->rootUseSSL = true;
        $page->staticPlugins = 'example.com';

        $GLOBALS['objPage'] = $page;

        $context = $this->getContaoContext('staticPlugins', $requestStack);

        $this->assertSame('https://example.com/foo/', $context->getStaticUrl());

        unset($GLOBALS['objPage']);
    }

    public function testReturnsAnEmptyStaticUrlIfTheBasePathIsEmpty(): void
    {
        $context = new ContaoContext(new RequestStack(), $this->mockContaoFramework(), 'staticPlugins');

        $this->assertSame('', $context->getStaticUrl());
    }

    public function testReadsTheSslConfigurationFromThePage(): void
    {
        $page = $this->getPageWithDetails();

        $GLOBALS['objPage'] = $page;

        $context = $this->getContaoContext('');

        $page->rootUseSSL = true;
        $this->assertTrue($context->isSecure());

        $page->rootUseSSL = false;
        $this->assertFalse($context->isSecure());

        unset($GLOBALS['objPage']);
    }

    public function testReadsTheSslConfigurationFromTheRequest(): void
    {
        unset($GLOBALS['objPage']);

        $request = new Request();
        $request->attributes = $this->createMock(ParameterBag::class);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $context = $this->getContaoContext('', $requestStack);

        $this->assertFalse($context->isSecure());

        $request->server->set('HTTPS', 'on');
        $this->assertTrue($context->isSecure());

        $request->server->set('HTTPS', 'off');
        $this->assertFalse($context->isSecure());
    }

    public function testDoesNotReadTheSslConfigurationIfThereIsNoRequest(): void
    {
        $context = $this->getContaoContext('');

        $this->assertFalse($context->isSecure());
    }

    private function getPageWithDetails(): PageModel
    {
        $finder = new ResourceFinder($this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao');

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.resource_finder', $finder);
        $container->setParameter('kernel.project_dir', $this->getFixturesDir());

        System::setContainer($container);

        $page = new PageModel();
        $page->type = 'root';
        $page->fallback = '1';
        $page->staticPlugins = '';

        return $page->loadDetails();
    }

    private function getContaoContext(string $field, RequestStack $requestStack = null): ContaoContext
    {
        $requestStack ??= new RequestStack();

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        return new ContaoContext($requestStack, $framework, $field);
    }
}
