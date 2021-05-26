<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\ResponseContext;

use Contao\CoreBundle\Routing\ResponseContext\CoreResponseContextFactory;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadManager\HtmlHeadManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class CoreResponseContextFactoryTest extends ContaoTestCase
{
    public function testResponseContext(): void
    {
        $responseAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseAccessor
            ->expects($this->once())
            ->method('setResponseContext')
        ;

        $factory = new CoreResponseContextFactory($responseAccessor);
        $responseContext = $factory->createResponseContext();

        $this->assertInstanceOf(ResponseHeaderBag::class, $responseContext->getHeaderBag());
        $this->assertSame($responseContext, $responseContext->finalize(new Response()));
    }

    public function testWebpageResponseContext(): void
    {
        $responseAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseAccessor
            ->expects($this->exactly(2))
            ->method('setResponseContext')
        ;

        $factory = new CoreResponseContextFactory($responseAccessor);
        $responseContext = $factory->createWebpageResponseContext();

        $this->assertInstanceOf(HtmlHeadManager::class, $responseContext->getHtmlHeadManager());

        // Assert that it correctly decorates the ResponseContext
        $this->assertInstanceOf(ResponseHeaderBag::class, $responseContext->getHeaderBag());
        $this->assertSame($responseContext, $responseContext->finalize(new Response()));
    }

    public function testContaoWebpageResponseContext(): void
    {
        $responseAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseAccessor
            ->expects($this->exactly(3))
            ->method('setResponseContext')
        ;

        $factory = new CoreResponseContextFactory($responseAccessor);
        $responseContext = $factory->createContaoWebpageResponseContext($this->createMock(PageModel::class));

        // Assert that it correctly decorates the WebpageResponseContext
        $this->assertInstanceOf(HtmlHeadManager::class, $responseContext->getHtmlHeadManager());
        $this->assertInstanceOf(ResponseHeaderBag::class, $responseContext->getHeaderBag());
        $this->assertSame($responseContext, $responseContext->finalize(new Response()));
    }
}
