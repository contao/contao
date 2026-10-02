<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\ContentElement;

use Contao\Config;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Controller\ContentElement\RootPageDependentElementsController;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

class RootPageDependentElementsControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME']);

        $this->resetStaticProperties([System::class, Config::class]);

        parent::tearDown();
    }

    public function testRendersTheContentElementOfTheCurrentRootPage(): void
    {
        $page = $this->createClassWithPropertiesStub(PageModel::class);
        $page->rootId = 1;

        $model = $this->createClassWithPropertiesStub(ContentModel::class);
        $model->rootPageDependentElements = serialize([1 => 'content-10', 2 => 'content-20']);

        $request = new Request([], [], ['_scope' => 'frontend', 'pageModel' => $page]);
        $requestStack = new RequestStack([$request]);

        $contentAdapter = $this->createAdapterMock(['findById']);
        $contentAdapter
            ->expects($this->once())
            ->method('findById')
            ->with('10')
            ->willReturn($this->createClassWithPropertiesStub(ContentModel::class))
        ;

        $controllerAdapter = $this->createAdapterMock(['getContentElement']);
        $controllerAdapter
            ->expects($this->once())
            ->method('getContentElement')
            ->willReturn('example-content')
        ;

        $framework = $this->createContaoFrameworkStub([Controller::class => $controllerAdapter, ContentModel::class => $contentAdapter]);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.cache.tag_manager', $this->createStub(CacheTagManager::class));
        $container->set('contao.framework', $framework);
        $container->set('contao.routing.page_finder', new PageFinder($framework, $this->createStub(RequestMatcherInterface::class), $requestStack));
        $container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());
        $container->set('request_stack', $requestStack);

        System::setContainer($container);

        $controller = new RootPageDependentElementsController();
        $controller->setContainer($container);

        $this->assertSame('example-content', $controller($request, $model, 'main')->getContent());
    }
}
