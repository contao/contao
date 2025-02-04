<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\Page;

use Contao\CoreBundle\Fixtures\Controller\Page\LayoutPageController;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Image\Preview\PreviewFactory;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Routing\ResponseContext\CoreResponseContextFactory;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Twig\Environment;

class AbstractLayoutPageControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_MIME']);

        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    public function testCreateAndRenderLayoutTemplate(): void
    {
        $layoutPageController = $this->getLayoutPageController();
        $response = $layoutPageController(new Request());

        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(
            [
                'page' => [
                    'layout' => 42,
                    'language' => 'en',
                ],
                'layout' => [
                    'modules' => 'a:0:{}',
                    'defaultImageDensities' => 'foo_densities',
                    'template' => 'foo_template',
                ],
                'head' => [],
                'preview_mode' => false,
                'locale' => 'en',
                'rtl' => false,
                'response_context' => [],
                'modules' => [],
                'templateName' => 'foo_template',
            ],
            $data,
        );
    }

    private function getLayoutPageController(): LayoutPageController
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->method('render')
            ->with('@Contao/page/regular.html.twig', ['some' => 'data'])
            ->willReturn('rendered page content')
        ;

        $layoutModel = $this->mockClassWithProperties(LayoutModel::class, [
            'modules' => serialize([]),
            'defaultImageDensities' => 'foo_densities',
            'template' => 'foo_template',
        ]);

        $layoutAdapter = $this->mockAdapter(['findById']);
        $layoutAdapter
            ->method('findById')
            ->with(42)
            ->willReturn($layoutModel)
        ;

        $framework = $this->mockContaoFramework([
            LayoutModel::class => $layoutAdapter,
        ]);

        $page = $this->mockClassWithProperties(PageModel::class);
        $page->layout = 42;
        $page->language = 'en';

        $request = Request::create('https://localhost');
        $request->attributes->set('pageModel', $page);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $pageFinder = new PageFinder(
            $framework,
            $this->createMock(RequestMatcherInterface::class),
            $requestStack,
        );

        $responseContext = new ResponseContext();
        $jsonLdManager = new JsonLdManager($responseContext);
        $responseContext->add($jsonLdManager);
        $responseContext->add(new HtmlHeadBag());

        $responseContextFactory = $this->createMock(CoreResponseContextFactory::class);
        $responseContextFactory
            ->method('createContaoWebpageResponseContext')
            ->with($page)
            ->willReturn($responseContext)
        ;

        $pictureFactory = $this->createMock(PictureFactory::class);
        $pictureFactory
            ->expects($this->once())
            ->method('setDefaultDensities')
            ->with('foo_densities')
        ;

        $previewFactory = $this->createMock(PreviewFactory::class);
        $previewFactory
            ->expects($this->once())
            ->method('setDefaultDensities')
            ->with('foo_densities')
        ;

        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->method('isPreviewMode')
            ->willReturn(false)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('twig', $twig);
        $container->set('contao.routing.page_finder', $pageFinder);
        $container->set('contao.routing.response_context_factory', $responseContextFactory);
        $container->set('contao.image.picture_factory', $pictureFactory);
        $container->set('contao.image.preview_factory', $previewFactory);
        $container->set('contao.security.token_checker', $tokenChecker);
        $container->set('contao.framework', $framework);

        $layoutPageController = new LayoutPageController();
        $layoutPageController->setContainer($container);

        System::setContainer($container);

        return $layoutPageController;
    }
}
