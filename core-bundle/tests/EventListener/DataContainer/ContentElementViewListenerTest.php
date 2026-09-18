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

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\CoreBundle\EventListener\DataContainer\ContentElementViewListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DC_Table;
use Contao\Image;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentElementViewListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testAdjustsThemeView(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['list']['sorting'] = 'foobar';

        $dc = $this->createClassWithPropertiesStub(DC_Table::class, [
            'parentTable' => 'tl_theme',
        ]);

        $listener = new ContentElementViewListener(
            $this->createContaoFrameworkStub(),
            $this->createStub(Connection::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(DcaUrlAnalyzer::class),
            $this->createStub(TranslatorInterface::class),
        );
        $listener->adjustListView($dc);

        $this->assertIsArray($GLOBALS['TL_DCA']['tl_content']['list']['sorting']);
    }

    public function testDoesNotAdjustOtherView(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['list']['sorting'] = 'foobar';

        $dc = $this->createClassWithPropertiesStub(DC_Table::class, [
            'parentTable' => 'tl_article',
        ]);

        $listener = new ContentElementViewListener(
            $this->createContaoFrameworkStub(),
            $this->createStub(Connection::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(DcaUrlAnalyzer::class),
            $this->createStub(TranslatorInterface::class),
        );
        $listener->adjustListView($dc);

        $this->assertSame('foobar', $GLOBALS['TL_DCA']['tl_content']['list']['sorting']);
    }

    #[DataProvider('gridViewProvider')]
    public function testGridView(array $row, string $expectedLabel, string $expectedClass, string $expectedPreview = '', string|array|false $queryResult = false): void
    {
        $contentModel = $this->createMock(ContentModel::class);
        $contentModel
            ->expects($this->once())
            ->method('setRow')
            ->with($row)
        ;

        $controllerAdapter = $this->createAdapterMock(['getContentElement']);
        $controllerAdapter
            ->expects($this->once())
            ->method('getContentElement')
            ->with($contentModel)
            ->willReturn('')
        ;

        $imageAdapter = $this->createAdapterStub(['getHtml']);
        $imageAdapter
            ->method('getHtml')
            ->willReturnArgument(0)
        ;

        $memberGroupAdapter = $this->createAdapterMock(['findMultipleByIds']);
        $memberGroupAdapter
            ->expects($row['groups'] && [-1] !== $row['groups'] ? $this->once() : $this->never())
            ->method('findMultipleByIds')
            ->willReturn(null)
        ;

        $framework = $this->createContaoFrameworkStub(
            [Controller::class => $controllerAdapter, Image::class => $imageAdapter],
            [ContentModel::class => $contentModel],
        );

        $connection = $this->createStub(Connection::class);

        if (\is_array($queryResult)) {
            $connection
                ->method('fetchNumeric')
                ->willReturn($queryResult)
            ;
        } elseif (\is_string($queryResult)) {
            $connection
                ->method('fetchOne')
                ->willReturn($queryResult)
            ;
        }

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturnArgument(0)
        ;

        $dcaUrlAnalyzer = $this->createStub(DcaUrlAnalyzer::class);
        $dcaUrlAnalyzer
            ->method('getEditUrl')
            ->willReturn('/contao?do=article&table=tl_content&act=edit&id='.$row['cteAlias'])
        ;

        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $dc = $this->createClassWithPropertiesStub(DC_Table::class, [
            'parentTable' => 'tl_article',
        ]);

        $GLOBALS['TL_DCA']['tl_content']['list']['sorting'] = 'foobar';

        $listener = new ContentElementViewListener(
            $framework,
            $connection,
            $urlGenerator,
            $dcaUrlAnalyzer,
            $translator,
        );
        $label = $listener->generateLabel($row, '', $dc);

        $this->assertSame($expectedLabel, $label[0]);
        $this->assertSame($expectedPreview, $label[1]);
        $this->assertSame($expectedClass, $label[2]);
    }

    public static function gridViewProvider(): iterable
    {
        yield [
            ['type' => 'text'],
            'text',
            'published',
        ];

        yield [
            ['type' => 'text', 'invisible' => true],
            'text',
            'unpublished',
        ];

        yield [
            ['type' => 'alias', 'cteAlias' => 42],
            'alias <a href="/contao?do=article&table=tl_content&act=edit&id=42" onclick="Backend.openModalIframe({ title: \'alias ID 42\', url:this.href + \'&amp;popup=1&amp;nb=1\' });return false">ID 42</a>',
            'published',
        ];

        yield [
            ['type' => 'alias', 'cteAlias' => 42],
            'Copyright <span class="tl_gray">[alias <a href="/contao?do=article&table=tl_content&act=edit&id=42" onclick="Backend.openModalIframe({ title: \'alias ID 42\', url:this.href + \'&amp;popup=1&amp;nb=1\' });return false">ID 42</a> (text)]</span>',
            'published',
            '',
            ['text', 'Copyright']
        ];

        yield [
            ['type' => 'article', 'articleAlias' => 42],
            'article <a href="contao_backend" onclick="Backend.openModalIframe({ title: \'article ID 42\', url:this.href + \'&amp;popup=1&amp;nb=1\' });return false">ID 42</a>',
            'published',
        ];

        yield [
            ['type' => 'article', 'articleAlias' => 42],
            'Home <span class="tl_gray">[article <a href="contao_backend" onclick="Backend.openModalIframe({ title: \'article ID 42\', url:this.href + \'&amp;popup=1&amp;nb=1\' });return false">ID 42</a>]</span>',
            'published',
            '',
            'Home',
        ];

        yield [
            ['type' => 'module', 'module' => 42],
            'module <a href="contao_backend" onclick="Backend.openModalIframe({ title: \'module ID 42\', url:this.href + \'&amp;popup=1&amp;nb=1\' });return false">ID 42</a>',
            'published',
        ];

        yield [
            ['type' => 'module', 'module' => 42],
            'Main navigation <span class="tl_gray">[module <a href="contao_backend" onclick="Backend.openModalIframe({ title: \'module ID 42\', url:this.href + \'&amp;popup=1&amp;nb=1\' });return false">ID 42</a> (navigation)]</span>',
            'published',
            '',
            ['navigation', 'Main navigation'],
        ];

        yield [
            ['type' => 'text', 'title' => 'Foobar'],
            'Foobar <span class="tl_gray">[text]</span>',
            'published',
        ];

        yield [
            ['type' => 'text', 'sectionHeadline' => ['value' => 'foobar', 'unit' => 'h1']],
            'text',
            'published',
            '<h1>foobar</h1>',
        ];

        yield [
            ['type' => 'text', 'protected' => true, 'groups' => []],
            'protected.svg text <span class="tl_gray">(MSC.protected)</span>',
            'published',
        ];

        yield [
            ['type' => 'text', 'protected' => true, 'groups' => [-1]],
            'protected.svg text <span class="tl_gray">(MSC.protected: MSC.guests)</span>',
            'published',
        ];

        yield [
            ['type' => 'headline', 'headline' => ['value' => '', 'unit' => 'h1']],
            'headline (h1)',
            'published',
        ];

        yield [
            ['type' => 'headline', 'headline' => ['value' => '', 'unit' => 'h1'], 'title' => 'Foobar'],
            'Foobar <span class="tl_gray">[headline (h1)]</span>',
            'published',
        ];

        yield [
            ['type' => 'text', 'start' => 1],
            'text <span class="tl_gray">(MSC.showFrom)</span>',
            'published',
        ];

        yield [
            ['type' => 'text', 'stop' => 1],
            'text <span class="tl_gray">(MSC.showTo)</span>',
            'published',
        ];

        yield [
            ['type' => 'text', 'start' => 1, 'stop' => 1],
            'text <span class="tl_gray">(MSC.showFromTo)</span>',
            'published',
        ];
    }

    public function testDisplaysErrorMessageIfGridPreviewThrowsException(): void
    {
        $contentModel = $this->createMock(ContentModel::class);
        $contentModel
            ->expects($this->once())
            ->method('setRow')
            ->with(['type' => 'text'])
        ;

        $controllerAdapter = $this->createAdapterMock(['getContentElement']);
        $controllerAdapter
            ->expects($this->once())
            ->method('getContentElement')
            ->with($contentModel)
            ->willThrowException(new \RuntimeException('foobar'))
        ;

        $framework = $this->createContaoFrameworkStub(
            [Controller::class => $controllerAdapter],
            [ContentModel::class => $contentModel],
        );

        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $dc = $this->createClassWithPropertiesStub(DC_Table::class, [
            'parentTable' => 'tl_article',
        ]);

        $listener = new ContentElementViewListener(
            $framework,
            $this->createStub(Connection::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(DcaUrlAnalyzer::class),
            $translator,
        );
        $label = $listener->generateLabel(['type' => 'text'], '', $dc);

        $this->assertSame('<p class="tl_error">foobar</p>', $label[1]);
    }
}
