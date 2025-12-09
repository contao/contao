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
            ->with('@Contao/layout/foo.html.twig')
            ->willReturn($templateInformation)
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
            ->with('pid')
            ->willReturn($pageModel)
        ;

        $articleAdapter = $this->createAdapterStub(['findById']);
        $articleAdapter
            ->method('findById')
            ->with(1)
            ->willReturn($articleModel)
        ;

        $layoutModel = $this->createClassWithPropertiesStub(LayoutModel::class);
        $layoutModel->type = 'modern';
        $layoutModel->template = 'layout/foo';

        $layoutAdapter = $this->createAdapterStub(['findById']);
        $layoutAdapter
            ->method('findById')
            ->with(2)
            ->willReturn($layoutModel)
        ;

        $framework = $this->createContaoFrameworkStub([
            ArticleModel::class => $articleAdapter,
            LayoutModel::class => $layoutAdapter,
        ]);

        $articleColumnListener = new ArticleColumnListener($inspector, $framework);

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
            ->with('pid')
            ->willReturn($pageModel)
        ;

        $articleAdapter = $this->createAdapterStub(['findById']);
        $articleAdapter
            ->method('findById')
            ->with(1)
            ->willReturn($articleModel)
        ;

        $layoutModel = $this->createClassWithPropertiesStub(LayoutModel::class);
        $layoutModel->type = 'default';
        $layoutModel->template = 'fe_page';

        $layoutAdapter = $this->createAdapterStub(['findById']);
        $layoutAdapter
            ->method('findById')
            ->with(2)
            ->willReturn($layoutModel)
        ;

        $framework = $this->createContaoFrameworkStub([
            ArticleModel::class => $articleAdapter,
            LayoutModel::class => $layoutAdapter,
        ]);

        $articleColumnListener = new ArticleColumnListener($this->createStub(Inspector::class), $framework);

        $dc = $this->createClassWithPropertiesStub(DataContainer::class);
        $dc->id = 1;

        $this->assertSame(
            'foo',
            $articleColumnListener->setSlotOptions('foo', $dc),
        );

        $this->assertArrayNotHasKey('TL_DCA', $GLOBALS);
    }
}
