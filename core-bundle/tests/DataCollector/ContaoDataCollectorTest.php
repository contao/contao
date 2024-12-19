<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DataCollector;

use Contao\ArticleModel;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\DataCollector\ContaoDataCollector;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\LayoutModel;
use Contao\Model;
use Contao\Model\Registry;
use Contao\PageModel;
use Imagine\Image\ImagineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class ContaoDataCollectorTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Model::class, Registry::class]);

        parent::tearDown();
    }

    public function testCollectsDataInBackEnd(): void
    {
        $collector = new ContaoDataCollector(
            $this->createMock(TokenChecker::class),
            $this->createMock(RequestStack::class),
            $this->createMock(ImagineInterface::class),
            $this->createMock(RouterInterface::class),
            $this->createMock(PageFinder::class),
        );

        $collector->setFramework($this->createMock(ContaoFramework::class));
        $collector->collect(new Request(), new Response());

        $version = ContaoCoreBundle::getVersion();

        $this->assertSame(
            [
                'version' => $version,
                'framework' => false,
                'frontend' => false,
                'preview' => false,
                'page' => '',
                'page_url' => '',
                'layout' => '',
                'layout_url' => '',
                'articles' => [],
                'template' => '',
            ],
            $collector->getSummary(),
        );

        $this->assertSame($version, $collector->getContaoVersion());
        $this->assertSame([], $collector->getAdditionalData());
        $this->assertSame('contao', $collector->getName());
    }

    public function testCollectsDataInFrontEnd(): void
    {
        $layout = $this->mockClassWithProperties(LayoutModel::class);
        $layout->name = 'Default';
        $layout->id = 2;
        $layout->template = 'fe_page';

        $adapter = $this->mockConfiguredAdapter(['findById' => $layout]);
        $articleModelAdapter = $this->mockConfiguredAdapter(['findByPid' => []]);
        $framework = $this->mockContaoFramework([LayoutModel::class => $adapter, ArticleModel::class => $articleModelAdapter]);

        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = 2;
        $page->title = 'Page';
        $page->layoutId = 2;

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->method('getCurrentPage')
            ->willReturn($page)
        ;

        $collector = new ContaoDataCollector(
            $this->createMock(TokenChecker::class),
            $this->createMock(RequestStack::class),
            $this->createMock(ImagineInterface::class),
            $this->createMock(RouterInterface::class),
            $pageFinder,
        );

        $collector->setFramework($framework);
        $collector->collect(new Request(), new Response());

        $this->assertSame(
            [
                'version' => ContaoCoreBundle::getVersion(),
                'framework' => true,
                'frontend' => true,
                'preview' => false,
                'page' => 'Page (ID 2)',
                'page_url' => '',
                'layout' => 'Default (ID 2)',
                'layout_url' => '',
                'articles' => [],
                'template' => 'fe_page',
            ],
            $collector->getSummary(),
        );

        $collector->reset();

        $this->assertSame([], $collector->getSummary());
    }

    public function testSetsTheFrontendPreviewFromTokenChecker(): void
    {
        $layout = $this->mockClassWithProperties(LayoutModel::class);
        $layout->name = 'Default';
        $layout->id = 2;
        $layout->template = 'fe_page';

        $adapter = $this->mockConfiguredAdapter(['findById' => $layout]);
        $articleModelAdapter = $this->mockConfiguredAdapter(['findByPid' => []]);
        $framework = $this->mockContaoFramework([LayoutModel::class => $adapter, ArticleModel::class => $articleModelAdapter]);

        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = 2;
        $page->title = 'Page';
        $page->layoutId = 2;

        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->expects($this->once())
            ->method('isPreviewMode')
            ->willReturn(true)
        ;

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->method('getCurrentPage')
            ->willReturn($page)
        ;

        $collector = new ContaoDataCollector(
            $tokenChecker,
            $this->createMock(RequestStack::class),
            $this->createMock(ImagineInterface::class),
            $this->createMock(RouterInterface::class),
            $pageFinder,
        );

        $collector->setFramework($framework);
        $collector->collect(new Request(), new Response());

        $this->assertSame(
            [
                'version' => ContaoCoreBundle::getVersion(),
                'framework' => true,
                'frontend' => true,
                'preview' => true,
                'page' => 'Page (ID 2)',
                'page_url' => '',
                'layout' => 'Default (ID 2)',
                'layout_url' => '',
                'articles' => [],
                'template' => 'fe_page',
            ],
            $collector->getSummary(),
        );

        unset($GLOBALS['objPage']);
    }

    public function testReturnsAnEmptyArrayIfTheKeyIsUnknown(): void
    {
        $collector = new ContaoDataCollector(
            $this->createMock(TokenChecker::class),
            $this->createMock(RequestStack::class),
            $this->createMock(ImagineInterface::class),
            $this->createMock(RouterInterface::class),
            $this->createMock(PageFinder::class),
        );

        $method = new \ReflectionMethod($collector, 'getData');

        $this->assertSame([], $method->invokeArgs($collector, ['foo']));
    }
}
