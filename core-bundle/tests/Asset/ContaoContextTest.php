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
use Contao\CoreBundle\Doctrine\Schema\SchemaProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\Model\Registry;
use Contao\PageModel;
use Contao\System;
use Doctrine\DBAL\Schema\Schema;
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
        $context = new ContaoContext(new RequestStack(), 'staticPlugins', true);

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

        $request->attributes->set('pageModel', $page);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $context = $this->getContaoContext('staticPlugins', $requestStack);

        $this->assertSame('/foobar', $context->getBasePath());
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

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $page = $this->getPageWithDetails();
        $page->rootUseSSL = $useSSL;
        $page->staticPlugins = $domain;

        $request->attributes = new ParameterBag(['pageModel' => $page]);

        $context = $this->getContaoContext('staticPlugins', $requestStack);

        $this->assertSame($expected, $context->getBasePath());
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

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $page = $this->getPageWithDetails();
        $page->rootUseSSL = true;
        $page->staticPlugins = 'example.com';

        $request->attributes = new ParameterBag(['pageModel' => $page]);

        $context = $this->getContaoContext('staticPlugins', $requestStack);

        $this->assertSame('https://example.com/foo/', $context->getStaticUrl());
    }

    public function testReturnsASlashIfTheBasePathIsEmpty(): void
    {
        $context = new ContaoContext(new RequestStack(), 'staticPlugins');

        $this->assertSame('/', $context->getStaticUrl());
    }

    public function testReadsTheSslConfigurationFromThePage(): void
    {
        $page = $this->getPageWithDetails();

        $request = new Request();
        $request->attributes = new ParameterBag(['pageModel' => $page]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $context = $this->getContaoContext('', $requestStack);

        $page->rootUseSSL = true;
        $this->assertTrue($context->isSecure());

        $page->rootUseSSL = false;
        $this->assertFalse($context->isSecure());
    }

    public function testReadsTheSslConfigurationFromTheRequest(): void
    {
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

        $schemaProvider = $this->createMock(SchemaProvider::class);
        $schemaProvider
            ->method('createSchema')
            ->willReturn(new Schema())
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.doctrine.schema_provider', $schemaProvider);
        $container->set('contao.resource_finder', $finder);
        $container->setParameter('kernel.project_dir', $this->getFixturesDir());

        System::setContainer($container);

        $page = new PageModel();
        $page->type = 'root';
        $page->fallback = true;
        $page->staticPlugins = '';

        return $page->loadDetails();
    }

    private function getContaoContext(string $field, RequestStack $requestStack = null): ContaoContext
    {
        $requestStack ??= new RequestStack();

        return new ContaoContext($requestStack, $field);
    }
}
