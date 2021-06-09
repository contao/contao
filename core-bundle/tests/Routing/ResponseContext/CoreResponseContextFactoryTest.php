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
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\PageModel;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
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
    }

    public function testWebpageResponseContext(): void
    {
        $responseAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseAccessor
            ->expects($this->once())
            ->method('setResponseContext')
        ;

        $factory = new CoreResponseContextFactory($responseAccessor);
        $responseContext = $factory->createWebpageResponseContext();

        $this->assertInstanceOf(HtmlHeadBag::class, $responseContext->get(HtmlHeadBag::class));
    }

    public function testContaoWebpageResponseContext(): void
    {
        $container = $this->getContainerWithContaoConfiguration();
        $container->set('request_stack', new RequestStack());
        System::setContainer($container);

        $responseAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseAccessor
            ->expects($this->once())
            ->method('setResponseContext')
        ;

        /** @var PageModel $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->title = 'My title';
        $pageModel->description = 'My description';
        $pageModel->robots = 'noindex,nofollow';

        $factory = new CoreResponseContextFactory($responseAccessor);
        $responseContext = $factory->createContaoWebpageResponseContext($pageModel);

        $this->assertInstanceOf(HtmlHeadBag::class, $responseContext->get(HtmlHeadBag::class));
        $this->assertSame('My title', $responseContext->get(HtmlHeadBag::class)->getTitle());
        $this->assertSame('My description', $responseContext->get(HtmlHeadBag::class)->getMetaDescription());
        $this->assertSame('noindex,nofollow', $responseContext->get(HtmlHeadBag::class)->getMetaRobots());
    }

    public function testDecodingAndCleanupOnContaoResponseContext(): void
    {
        $container = $this->getContainerWithContaoConfiguration();
        $container->set('request_stack', new RequestStack());
        System::setContainer($container);

        /** @var PageModel $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->title = 'We went from Alpha &#62; Omega ';
        $pageModel->description = 'My description <strong>contains</strong> HTML<br>.';

        $factory = new CoreResponseContextFactory($this->createMock(ResponseContextAccessor::class));
        $responseContext = $factory->createContaoWebpageResponseContext($pageModel);

        $this->assertSame('We went from Alpha > Omega ', $responseContext->get(HtmlHeadBag::class)->getTitle());
        $this->assertSame('My description contains HTML.', $responseContext->get(HtmlHeadBag::class)->getMetaDescription());
    }
}
