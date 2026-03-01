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

use Contao\CoreBundle\ContentComposition\ContentComposition;
use Contao\CoreBundle\ContentComposition\ContentCompositionBuilder;
use Contao\CoreBundle\Controller\Page\ErrorPageController;
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\ResponseContext\CoreResponseContextFactory;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\LayoutTemplate;
use Contao\LayoutModel;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ErrorPageControllerTest extends TestCase
{
    public function testRendersThePage(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, [
            'type' => 'error_404',
            'autoforward' => false,
        ]);

        $controller = $this->getErrorPageController();

        $response = $controller($pageModel, new Request());

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('<content>', $response->getContent());
    }

    public function testSupportsContentComposition(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, [
            'type' => 'error_404',
            'autoforward' => false,
        ]);

        $controller = new ErrorPageController($this->createStub(UriSigner::class));

        $this->assertTrue($controller->supportsContentComposition($pageModel));
    }

    public function testDisablesContentCompositionWithAutoforward(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, [
            'type' => 'error_404',
            'autoforward' => true,
        ]);

        $controller = new ErrorPageController($this->createStub(UriSigner::class));

        $this->assertFalse($controller->supportsContentComposition($pageModel));
    }

    public function testAlwaysSupportsContentCompositionFor503Page(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, [
            'type' => 'error_503',
            'autoforward' => true,
        ]);

        $controller = new ErrorPageController($this->createStub(UriSigner::class));

        $this->assertTrue($controller->supportsContentComposition($pageModel));
    }

    public function testThrowsForwardPageNotFoundException(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, [
            'type' => 'error_404',
            'autoforward' => true,
            'jumpTo' => 74656,
        ]);

        $pageAdapter = $this->createAdapterMock(['findById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(74656)
            ->willReturn(null)
        ;

        $framework = $this->createContaoFrameworkStub([
            PageModel::class => $pageAdapter,
        ]);

        $controller = $this->getErrorPageController($framework);

        $this->expectException(ForwardPageNotFoundException::class);
        $this->expectExceptionMessage('Forward page not found');

        $controller($pageModel, new Request());
    }

    public function testRedirectsToForwardPage(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, [
            'type' => 'error_401',
            'autoforward' => true,
            'jumpTo' => 74656,
        ]);

        $targetPage = $this->createStub(PageModel::class);

        $pageAdapter = $this->createAdapterMock(['findById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(74656)
            ->willReturn($targetPage)
        ;

        $framework = $this->createContaoFrameworkStub([
            PageModel::class => $pageAdapter,
        ]);

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($targetPage, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/foobar')
        ;

        $request = Request::create('https://example.com/moobar');

        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner
            ->expects($this->once())
            ->method('sign')
            ->with('https://example.com/foobar?'.http_build_query(['redirect' => 'https://example.com/moobar']))
            ->willReturn('<signed uri>')
        ;

        $controller = $this->getErrorPageController($framework, $contentUrlGenerator, $uriSigner);
        $response = $controller($pageModel, $request);

        $this->assertSame(Response::HTTP_SEE_OTHER, $response->getStatusCode());
        $this->assertSame('<signed uri>', $response->headers->get('Location'));
    }

    private function getErrorPageController(ContaoFramework|null $framework = null, ContentUrlGenerator|null $contentUrlGenerator = null, UriSigner|null $uriSigner = null): ErrorPageController
    {
        if (!$framework) {
            $layoutAdapter = $this->createAdapterStub(['findById']);
            $layoutAdapter
                ->method('findById')
                ->willReturn($this->createClassWithPropertiesStub(LayoutModel::class, ['type' => 'modern']))
            ;

            $framework = $this->createContaoFrameworkStub([
                LayoutModel::class => $layoutAdapter,
            ]);
        }

        $responseContextFactory = $this->createStub(CoreResponseContextFactory::class);
        $responseContextFactory
            ->method('createContaoWebpageResponseContext')
            ->willReturn(new ResponseContext())
        ;

        $contentCompositionBuilder = $this->createStub(ContentCompositionBuilder::class);
        $contentCompositionBuilder
            ->method('buildLayoutTemplate')
            ->willReturn(new LayoutTemplate('<template>', static fn () => new Response('<content>')))
        ;

        $contentCompositionBuilder
            ->method('setResponseContext')
            ->willReturnSelf()
        ;

        $contentComposition = $this->createStub(ContentComposition::class);
        $contentComposition
            ->method('createContentCompositionBuilder')
            ->willReturn($contentCompositionBuilder)
        ;

        if (!$contentUrlGenerator) {
            $contentUrlGenerator = $this->createStub(ContentUrlGenerator::class);
        }

        if (!$uriSigner) {
            $uriSigner = $this->createStub(UriSigner::class);
        }

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('event_dispatcher', $this->createStub(EventDispatcherInterface::class));
        $container->set('contao.routing.response_context_factory', $responseContextFactory);
        $container->set('contao.content_composition', $contentComposition);
        $container->set('contao.routing.page_registry', $this->createStub(PageRegistry::class));
        $container->set('contao.routing.content_url_generator', $contentUrlGenerator);

        $controller = new ErrorPageController($uriSigner);
        $controller->setContainer($container);

        return $controller;
    }
}
