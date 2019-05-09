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

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContaoContextTest extends TestCase
{
    public function testReturnsAnEmptyBasePathInDebugMode(): void
    {
        $context = new ContaoContext(new RequestStack(), 'staticPlugins', true);

        $this->assertSame('', $context->getBasePath());
    }

    public function testReturnsAnEmptyBasePathIfThereIsNoRequest(): void
    {
        $context = $this->mockContaoContext('staticPlugins');

        $this->assertSame('', $context->getBasePath());
    }

    public function testReturnsAnEmptyBasePathIfThePageDoesNotDefineIt(): void
    {
        $page = $this->mockPageWithDetails();

        $GLOBALS['objPage'] = $page;

        $context = $this->mockContaoContext('staticPlugins');

        $this->assertSame('', $context->getBasePath());

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

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $page = $this->mockPageWithDetails();
        $page->rootUseSSL = $useSSL;
        $page->staticPlugins = $domain;

        $GLOBALS['objPage'] = $page;

        $context = $this->mockContaoContext('staticPlugins', $requestStack);

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

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $page = $this->mockPageWithDetails();
        $page->rootUseSSL = true;
        $page->staticPlugins = 'example.com';

        $GLOBALS['objPage'] = $page;

        $context = $this->mockContaoContext('staticPlugins', $requestStack);

        $this->assertSame('https://example.com/foo/', $context->getStaticUrl());
    }

    public function testReturnsAnEmptyStaticUrlIfTheBasePathIsEmpty(): void
    {
        $context = new ContaoContext(new RequestStack(), 'staticPlugins');

        $this->assertSame('', $context->getStaticUrl());
    }

    public function testReadsTheSslConfigurationFromThePage(): void
    {
        $page = $this->mockPageWithDetails();

        $GLOBALS['objPage'] = $page;

        $context = $this->mockContaoContext('');

        $page->rootUseSSL = true;
        $this->assertTrue($context->isSecure());

        $page->rootUseSSL = false;
        $this->assertFalse($context->isSecure());

        unset($GLOBALS['objPage']);
    }

    public function testReadsTheSslConfigurationFromTheRequest(): void
    {
        $request = new Request();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $context = $this->mockContaoContext('', $requestStack);

        $this->assertFalse($context->isSecure());

        $request->server->set('HTTPS', 'on');
        $this->assertTrue($context->isSecure());

        $request->server->set('HTTPS', 'off');
        $this->assertFalse($context->isSecure());
    }

    public function testDoesNotReadTheSslConfigurationIfThereIsNoRequest(): void
    {
        $context = $this->mockContaoContext('');

        $this->assertFalse($context->isSecure());
    }

    private function mockPageWithDetails(): PageModel
    {
        $finder = new ResourceFinder($this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao');

        $container = $this->mockContainer();
        $container->set('contao.resource_finder', $finder);

        System::setContainer($container);

        $page = new PageModel();
        $page->type = 'root';
        $page->fallback = true;
        $page->staticPlugins = '';

        return $page->loadDetails();
    }

    private function mockContaoContext(string $field, RequestStack $requestStack = null): ContaoContext
    {
        if (null === $requestStack) {
            $requestStack = new RequestStack();
        }

        return new ContaoContext($requestStack, $field);
    }
}
