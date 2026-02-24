<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\Controller\Page;

use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\ContentComposition\ContentComposition;
use Contao\CoreBundle\ContentComposition\ContentCompositionBuilder;
use Contao\CoreBundle\Controller\Page\RegularPageController;
use Contao\CoreBundle\EventListener\SubrequestCacheSubscriber;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\ResponseContext\CoreResponseContextFactory;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\LayoutTemplate;
use Contao\FrontendIndex;
use Contao\LayoutModel;
use Contao\PageModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

class RegularPageControllerTest extends TestCase
{
    public function testHandlesNonModernLayoutType(): void
    {
        $page = $this->createClassWithPropertiesStub(PageModel::class, ['layout' => 1]);
        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, ['type' => 'default']);

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->willReturnMap([[1, $layout]])
        ;

        $frontendIndex = $this->createMock(FrontendIndex::class);
        $frontendIndex
            ->expects($this->once())
            ->method('renderLegacy')
            ->with($page)
            ->willReturn(new Response('<alternative content>'))
        ;

        $framework = $this->createContaoFrameworkStub(
            [
                LayoutModel::class => $layoutAdapter,
            ],
            [
                FrontendIndex::class => $frontendIndex,
            ],
        );

        $controller = $this->getRegularPageController(
            $framework,
            $this->getContentComposition(false),
        );

        $this->assertSame(
            '<alternative content>',
            $controller($page)->getContent(),
        );
    }

    #[DataProvider('providePageCacheSettings')]
    public function testAppliesCacheHeaders(array $pageAttributes, string $expectedCacheControl): void
    {
        $page = $this->createClassWithPropertiesStub(PageModel::class, $pageAttributes);
        $response = $this->getRegularPageController()($page);

        $this->assertSame($expectedCacheControl, $response->headers->get('Cache-Control'));
        $this->assertTrue($response->headers->has(SubrequestCacheSubscriber::MERGE_CACHE_HEADER));
    }

    public function testSetsAndFinalizesResponseContext(): void
    {
        $responseContextFactory = $this->createStub(CoreResponseContextFactory::class);
        $responseContextFactory
            ->method('createContaoWebpageResponseContext')
            ->willReturn($responseContext = new ResponseContext())
        ;

        $finalizedResponse = null;

        $responseContextAccessor = $this->createStub(ResponseContextAccessor::class);
        $responseContextAccessor
            ->method('finalizeCurrentContext')
            ->willReturnCallback(
                static function (Response $response) use (&$finalizedResponse, &$responseContextAccessor): ResponseContextAccessor {
                    $finalizedResponse = $response;

                    return $responseContextAccessor;
                },
            )
        ;

        $controller = $this->getRegularPageController(
            contentComposition: $this->getContentComposition(expectedResponseContext: $responseContext),
            responseContextFactory: $responseContextFactory,
            responseContextAccessor: $responseContextAccessor,
        );

        $response = $controller($this->createClassWithPropertiesStub(PageModel::class));

        $this->assertSame($response, $finalizedResponse);
    }

    public static function providePageCacheSettings(): iterable
    {
        yield 'disabled' => [
            ['cache' => 0],
            'no-cache, no-store, private',
        ];

        yield 'shared' => [
            ['cache' => 60],
            'public, s-maxage=60',
        ];

        yield 'private' => [
            ['clientCache' => 10],
            'max-age=10, private',
        ];
    }

    private function getRegularPageController(ContaoFramework|null $framework = null, ContentComposition|null $contentComposition = null, CoreResponseContextFactory|null $responseContextFactory = null, ResponseContextAccessor|null $responseContextAccessor = null): RegularPageController
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

        if (!$responseContextFactory) {
            $responseContextFactory = $this->createStub(CoreResponseContextFactory::class);
            $responseContextFactory
                ->method('createContaoWebpageResponseContext')
                ->willReturn(new ResponseContext())
            ;
        }

        $controller = new RegularPageController();

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('event_dispatcher', $this->createStub(EventDispatcherInterface::class));
        $container->set('contao.cache.tag_manager', $this->createStub(CacheTagManager::class));
        $container->set('contao.routing.response_context_accessor', $responseContextAccessor ?? $this->createStub(ResponseContextAccessor::class));
        $container->set('contao.routing.response_context_factory', $responseContextFactory);
        $container->set('contao.content_composition', $contentComposition ?? $this->getContentComposition());
        $container->set('contao.routing.page_registry', $this->createStub(PageRegistry::class));

        $controller->setContainer($container);

        return $controller;
    }

    private function getContentComposition(bool $build = true, ResponseContext|null $expectedResponseContext = null): ContentComposition
    {
        $contentCompositionBuilder = $this->createMock(ContentCompositionBuilder::class);

        if ($expectedResponseContext) {
            $contentCompositionBuilder
                ->expects($this->once())
                ->method('setResponseContext')
                ->with($expectedResponseContext)
                ->willReturnSelf()
            ;
        } else {
            $contentCompositionBuilder
                ->method('setResponseContext')
                ->willReturnSelf()
            ;
        }

        $contentCompositionBuilder
            ->expects($build ? $this->once() : $this->never())
            ->method('buildLayoutTemplate')
            ->willReturn(new LayoutTemplate('<template>', static fn () => new Response('<content>')))
        ;

        $contentComposition = $this->createStub(ContentComposition::class);
        $contentComposition
            ->method('createContentCompositionBuilder')
            ->willReturn($contentCompositionBuilder)
        ;

        return $contentComposition;
    }
}
