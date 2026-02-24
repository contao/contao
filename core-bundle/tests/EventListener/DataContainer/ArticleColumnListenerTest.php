<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\ArticleModel;
use Contao\CoreBundle\EventListener\DataContainer\ArticleColumnListener;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Inspector\TemplateInformation;
use Contao\DataContainer;
use Contao\LayoutModel;
use Contao\PageModel;
use Twig\Source;

class ArticleColumnListenerTest extends TestCase
{
    public function testSetsSlotOptions(): void
    {
        $templateInformation = new TemplateInformation(
            new Source('', ''),
            slots: ['foo', 'bar'],
        );

        $inspector = $this->createStub(Inspector::class);
        $inspector
            ->method('inspectTemplate')
            ->willReturnMap([['@Contao/layout/foo.html.twig', $templateInformation]])
        ;

        $pageModel = $this->createClassWithPropertiesMock(PageModel::class);
        $pageModel
            ->expects($this->once())
            ->method('loadDetails')
            ->willReturnSelf()
        ;

        $pageModel->layout = 2;

        $articleModel = $this->createStub(ArticleModel::class);
        $articleModel
            ->method('getRelated')
            ->willReturnMap([['pid', $pageModel]])
        ;

        $articleAdapter = $this->createAdapterStub(['findById']);
        $articleAdapter
            ->method('findById')
            ->willReturnMap([[1, $articleModel]])
        ;

        $layoutModel = $this->createClassWithPropertiesStub(LayoutModel::class);
        $layoutModel->type = 'modern';
        $layoutModel->template = 'layout/foo';

        $layoutAdapter = $this->createAdapterStub(['findById']);
        $layoutAdapter
            ->method('findById')
            ->willReturnMap([[2, $layoutModel]])
        ;

        $framework = $this->createContaoFrameworkStub([
            ArticleModel::class => $articleAdapter,
            LayoutModel::class => $layoutAdapter,
        ]);

        $articleColumnListener = new ArticleColumnListener(
            $inspector,
            $framework,
            $this->createStub(PageRegistry::class),
        );

        $dc = $this->createClassWithPropertiesStub(DataContainer::class);
        $dc->id = 1;

        $this->assertSame(
            'foo',
            $articleColumnListener->setSlotOptions('foo', $dc),
        );

        $this->assertSame(
            [
                'foo' => '{% slot foo %}',
                'bar' => '{% slot bar %}',
            ],
            $GLOBALS['TL_DCA']['tl_article']['fields']['inColumn']['options'],
        );

        $this->assertArrayNotHasKey(
            'options_callback',
            $GLOBALS['TL_DCA']['tl_article']['fields']['inColumn'],
        );

        unset($GLOBALS['TL_DCA']);
    }

    public function testDoesNotSetSlotOptionsForLegacyLayouts(): void
    {
        $pageModel = $this->createClassWithPropertiesMock(PageModel::class);
        $pageModel
            ->expects($this->once())
            ->method('loadDetails')
            ->willReturnSelf()
        ;

        $pageModel->layout = 2;

        $articleModel = $this->createStub(ArticleModel::class);
        $articleModel
            ->method('getRelated')
            ->willReturnMap([['pid', $pageModel]])
        ;

        $articleAdapter = $this->createAdapterStub(['findById']);
        $articleAdapter
            ->method('findById')
            ->willReturnMap([[1, $articleModel]])
        ;

        $layoutModel = $this->createClassWithPropertiesStub(LayoutModel::class);
        $layoutModel->type = 'default';
        $layoutModel->template = 'fe_page';

        $layoutAdapter = $this->createAdapterStub(['findById']);
        $layoutAdapter
            ->method('findById')
            ->willReturnMap([[2, $layoutModel]])
        ;

        $framework = $this->createContaoFrameworkStub([
            ArticleModel::class => $articleAdapter,
            LayoutModel::class => $layoutAdapter,
        ]);

        $articleColumnListener = new ArticleColumnListener(
            $this->createStub(Inspector::class),
            $framework,
            $this->createStub(PageRegistry::class),
        );

        $dc = $this->createClassWithPropertiesStub(DataContainer::class);
        $dc->id = 1;

        $this->assertSame(
            'foo',
            $articleColumnListener->setSlotOptions('foo', $dc),
        );

        $this->assertArrayNotHasKey('TL_DCA', $GLOBALS);
    }

    public function testSetsSlotOptionsForRouteWithCustomTemplate(): void
    {
        $templateInformation = new TemplateInformation(
            new Source('', ''),
            slots: ['foo', 'bar'],
        );

        $inspector = $this->createStub(Inspector::class);
        $inspector
            ->method('inspectTemplate')
            ->willReturnMap([['@Contao/layout/foo.html.twig', $templateInformation]])
        ;

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class);

        $articleModel = $this->createStub(ArticleModel::class);
        $articleModel
            ->method('getRelated')
            ->willReturnMap([['pid', $pageModel]])
        ;

        $articleAdapter = $this->createAdapterStub(['findById']);
        $articleAdapter
            ->method('findById')
            ->willReturnMap([[1, $articleModel]])
        ;

        $framework = $this->createContaoFrameworkStub([
            ArticleModel::class => $articleAdapter,
        ]);

        $pageRoute = $this->createStub(PageRoute::class);
        $pageRoute
            ->method('getDefault')
            ->willReturnMap([['_template', 'layout/foo']])
        ;

        $pageRegistry = $this->createStub(PageRegistry::class);
        $pageRegistry
            ->method('getRoute')
            ->willReturnMap([[$pageModel, $pageRoute]])
        ;

        $articleColumnListener = new ArticleColumnListener(
            $inspector,
            $framework,
            $pageRegistry,
        );

        $dc = $this->createClassWithPropertiesStub(DataContainer::class);
        $dc->id = 1;

        $this->assertSame(
            'foo',
            $articleColumnListener->setSlotOptions('foo', $dc),
        );

        $this->assertSame(
            [
                'foo' => '{% slot foo %}',
                'bar' => '{% slot bar %}',
            ],
            $GLOBALS['TL_DCA']['tl_article']['fields']['inColumn']['options'],
        );

        $this->assertArrayNotHasKey(
            'options_callback',
            $GLOBALS['TL_DCA']['tl_article']['fields']['inColumn'],
        );

        unset($GLOBALS['TL_DCA']);
    }
}
