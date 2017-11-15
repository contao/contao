<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Asset;

use Contao\Config;
use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Model\Registry;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContaoContextTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $context = $this->mockContaoContext('staticPlugins');

        $this->assertInstanceOf('Contao\CoreBundle\Asset\ContaoContext', $context);
    }

    public function testReturnsAnEmptyBasePathInDebugMode(): void
    {
        $context = new ContaoContext($this->mockContaoFramework(), new RequestStack(), 'staticPlugins', true);

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
     * @param string $domain
     * @param bool   $useSSL
     * @param string $basePath
     * @param string $expected
     *
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

    /**
     * @param string $domain
     * @param bool   $useSSL
     * @param string $basePath
     * @param string $expected
     *
     * @dataProvider getBasePaths
     */
    public function testReadsTheBasePathFromTheGlobalConfigurationIfThePageDoesNotDefineIt(string $domain, bool $useSSL, string $basePath, string $expected): void
    {
        $request = $this->createMock(Request::class);

        $request
            ->expects($this->once())
            ->method('getBasePath')
            ->willReturn($basePath)
        ;

        $request
            ->expects($this->once())
            ->method('isSecure')
            ->willReturn($useSSL)
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $config = $this->mockConfiguredAdapter(['get' => $domain]);
        $framework = $this->mockContaoFramework([Config::class => $config]);
        $context = $this->mockContaoContext('staticPlugins', $requestStack, $framework);

        $this->assertSame($expected, $context->getBasePath());
    }

    public function getBasePaths(): array
    {
        return [
            ['example.com', true, '', 'https://example.com'],
            ['example.com', false, '', 'http://example.com'],
            ['example.com', true, '/foo', 'https://example.com/foo'],
            ['example.com', false, '/foo', 'http://example.com/foo'],
            ['example.ch', false, '/bar', 'http://example.ch/bar'],
        ];
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

    /**
     * Mocks a page model with details.
     *
     * @return PageModel
     */
    private function mockPageWithDetails(): PageModel
    {
        // Ensure to use the fixtures class
        Registry::getInstance();

        $page = new PageModel();
        $page->type = 'root';
        $page->fallback = true;
        $page->staticPlugins = '';

        return $page->loadDetails();
    }

    /**
     * Mocks a Contao context.
     *
     * @param string                        $field
     * @param RequestStack|null             $requestStack
     * @param ContaoFrameworkInterface|null $framework
     *
     * @return ContaoContext
     */
    private function mockContaoContext(string $field, RequestStack $requestStack = null, ContaoFrameworkInterface $framework = null): ContaoContext
    {
        if (null === $requestStack) {
            $requestStack = new RequestStack();
        }

        if (null === $framework) {
            $framework = $this->mockContaoFramework();
        }

        return new ContaoContext($framework, $requestStack, $field);
    }
}
