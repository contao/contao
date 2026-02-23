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

use Contao\CoreBundle\EventListener\DataContainer\ArticleColumnListener;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Inspector\TemplateInformation;
use Contao\DataContainer;
use Contao\LayoutModel;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Source;

class ArticleColumnListenerTest extends TestCase
{
    public function testReturnsSlotsFromPageTemplate(): void
    {
        $dc = $this->createMock(DataContainer::class);
        $dc
            ->expects($this->once())
            ->method('getCurrentRecord')
            ->willReturn(['pid' => 42])
        ;

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class);

        $pageAdapter = $this->createAdapterMock(['findWithDetails']);
        $pageAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(42)
            ->willReturn($pageModel)
        ;

        $framework = $this->createContaoFrameworkStub([
            PageModel::class => $pageAdapter,
        ]);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('getPageTemplate')
            ->with($pageModel)
            ->willReturn('layout/foo')
        ;

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

        $articleColumnListener = new ArticleColumnListener(
            $framework,
            $pageRegistry,
            $inspector,
            $this->createStub(RequestStack::class),
            $this->createStub(Connection::class),
        );

        $options = $articleColumnListener($dc);

        $this->assertSame(
            [
                'foo' => '{% slot foo %}',
                'bar' => '{% slot bar %}',
            ],
            $options,
        );
    }

    public function testReturnsSectionsFromDefaultLayout(): void
    {
        $dc = $this->createMock(DataContainer::class);
        $dc
            ->expects($this->once())
            ->method('getCurrentRecord')
            ->willReturn(['pid' => 42])
        ;

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['layout' => 2]);

        $pageAdapter = $this->createAdapterMock(['findWithDetails']);
        $pageAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(42)
            ->willReturn($pageModel)
        ;

        $layoutModel = $this->createClassWithPropertiesStub(LayoutModel::class, ['type' => 'modern', 'template' => 'layout/foo']);

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn($layoutModel)
        ;

        $framework = $this->createContaoFrameworkStub([
            PageModel::class => $pageAdapter,
            LayoutModel::class => $layoutAdapter,
        ]);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('getPageTemplate')
            ->with($pageModel)
            ->willReturn(null)
        ;

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

        $articleColumnListener = new ArticleColumnListener(
            $framework,
            $pageRegistry,
            $inspector,
            $this->createStub(RequestStack::class),
            $this->createStub(Connection::class),
        );

        $options = $articleColumnListener($dc);

        $this->assertSame(
            [
                'foo' => '{% slot foo %}',
                'bar' => '{% slot bar %}',
            ],
            $options,
        );
    }

    public function testReturnsSlotsFromModernLayout(): void
    {
        $dc = $this->createMock(DataContainer::class);
        $dc
            ->expects($this->once())
            ->method('getCurrentRecord')
            ->willReturn(['pid' => 42])
        ;

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['layout' => 2]);

        $pageAdapter = $this->createAdapterMock(['findWithDetails']);
        $pageAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(42)
            ->willReturn($pageModel)
        ;

        $layoutModel = $this->createClassWithPropertiesStub(
            LayoutModel::class,
            ['type' => 'default', 'modules' => serialize([
                ['mod' => 0, 'enable' => true, 'col' => 'foo'],
                ['mod' => 0, 'enable' => true, 'col' => 'bar'],
                ['mod' => 1, 'enable' => true, 'col' => 'baz'],
                ['mod' => 0, 'enable' => false, 'col' => 'bak'],
            ])],
        );

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn($layoutModel)
        ;

        $framework = $this->createContaoFrameworkStub([
            PageModel::class => $pageAdapter,
            LayoutModel::class => $layoutAdapter,
        ]);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('getPageTemplate')
            ->with($pageModel)
            ->willReturn(null)
        ;

        $articleColumnListener = new ArticleColumnListener(
            $framework,
            $pageRegistry,
            $this->createStub(Inspector::class),
            $this->createStub(RequestStack::class),
            $this->createStub(Connection::class),
        );

        $options = $articleColumnListener($dc);

        $this->assertSame(
            [
                'bar' => 'bar',
                'foo' => 'foo',
            ],
            $options,
        );
    }
}
