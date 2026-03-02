<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\Widget;

use Contao\CoreBundle\EventListener\Widget\TitleTagListener;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Tests\TestCase;
use Contao\LayoutModel;
use Contao\Model;
use Contao\PageModel;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TitleTagListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['objPage']);

        parent::tearDown();
    }

    public function testReturnsDefaultIfNoPageModel(): void
    {
        $listener = new TitleTagListener();

        $this->assertSame('', $listener($this->createStub(Model::class)));
    }

    public function testReturnsDefaultIfNoLayoutModel(): void
    {
        $page = $this->createClassWithPropertiesMock(PageModel::class, ['layout' => 1]);
        $page
            ->expects($this->once())
            ->method('loadDetails')
        ;

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null)
        ;

        $framework = $this->createContaoFrameworkStub([
            LayoutModel::class => $layoutAdapter,
        ]);

        $container = new ContainerBuilder();
        $container->set('contao.framework', $framework);

        $listener = new TitleTagListener();
        $listener->setContainer($container);

        $this->assertSame('', $listener($page));
    }

    public function testReturnsDefaultLayoutTitleTag(): void
    {
        $page = $this->createClassWithPropertiesMock(PageModel::class, ['layout' => 1]);
        $page
            ->expects($this->once())
            ->method('loadDetails')
        ;

        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, [
            'type' => 'default',
            'titleTag' => '{{page::pageTitle}} - {{page::rootPageTitle}}',
        ]);

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($layout)
        ;

        $framework = $this->createContaoFrameworkStub([
            LayoutModel::class => $layoutAdapter,
        ]);

        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->expects($this->exactly(2))
            ->method('replaceInline')
            ->willReturnMap([
                ['', ''],
                [' - {{page::rootPageTitle}}', ' - Example Website'],
            ])
        ;

        $container = new ContainerBuilder();
        $container->set('contao.framework', $framework);
        $container->set('contao.insert_tag.parser', $insertTagParser);

        $listener = new TitleTagListener();
        $listener->setContainer($container);

        $this->assertSame('%s - Example Website', $listener($page));
    }

    public function testThrowsExceptionWithUnknownLayoutType(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown layout type "foobar"');

        $page = $this->createClassWithPropertiesMock(PageModel::class, ['layout' => 1]);
        $page
            ->expects($this->once())
            ->method('loadDetails')
        ;

        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, ['type' => 'foobar']);

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($layout)
        ;

        $framework = $this->createContaoFrameworkStub([
            LayoutModel::class => $layoutAdapter,
        ]);

        $container = new ContainerBuilder();
        $container->set('contao.framework', $framework);

        $listener = new TitleTagListener();
        $listener->setContainer($container);

        $listener($page);
    }

    public function testReturnsModernLayoutTitleTag(): void
    {
        $page = $this->createClassWithPropertiesMock(PageModel::class, [
            'layout' => 1,
            'rootTitle' => 'Example Website',
        ]);

        $page
            ->expects($this->once())
            ->method('loadDetails')
        ;

        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, ['type' => 'modern']);

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($layout)
        ;

        $framework = $this->createContaoFrameworkStub([
            LayoutModel::class => $layoutAdapter,
        ]);

        $container = new ContainerBuilder();
        $container->set('contao.framework', $framework);

        $listener = new TitleTagListener();
        $listener->setContainer($container);

        $this->assertSame('%s - Example Website', $listener($page));
    }
}
