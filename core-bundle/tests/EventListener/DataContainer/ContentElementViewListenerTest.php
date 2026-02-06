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
use Contao\CoreBundle\EventListener\DataContainer\ContentElementViewListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DC_Table;
use Contao\Image;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentElementViewListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    #[DataProvider('gridViewProvider')]
    public function testGridView(array $row, string $expectedLabel, string $expectedClass, string $expectedPreview = ''): void
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
            ->with('protected.svg')
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

        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $dc = $this->createClassWithPropertiesStub(DC_Table::class, [
            'parentTable' => 'tl_article',
        ]);

        $GLOBALS['TL_DCA']['tl_content']['list']['sorting'] = 'foobar';

        $listener = new ContentElementViewListener($framework, $translator);
        $listener($dc);

        // The DCA sorting key must not be changed
        $this->assertSame('foobar', $GLOBALS['TL_DCA']['tl_content']['list']['sorting']);

        $this->assertIsCallable($GLOBALS['TL_DCA']['tl_content']['list']['label']['label_callback']);

        $label = $GLOBALS['TL_DCA']['tl_content']['list']['label']['label_callback']($row);

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
            'alias ID 42',
            'published',
        ];

        yield [
            ['type' => 'text', 'title' => 'foobar'],
            'foobar <span class="tl_gray">[text]</span>',
            'published',
        ];

        yield [
            ['type' => 'text', 'sectionHeadline' => ['value' => 'foobar', 'unit' => 'h1']],
            'text',
            'published',
            '<h1>foobar</h1>',
        ];

        yield [
            ['type' => 'text', 'protected' => true],
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
            ['type' => 'headline', 'headline' => ['value' => '', 'unit' => 'h1'], 'title' => 'foobar'],
            'foobar <span class="tl_gray">[headline (h1)]</span>',
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

        $framework = $this->createContaoFrameworkMock(
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

        $listener = new ContentElementViewListener($framework, $translator);
        $listener($dc);

        $this->assertIsCallable($GLOBALS['TL_DCA']['tl_content']['list']['label']['label_callback']);

        $label = $GLOBALS['TL_DCA']['tl_content']['list']['label']['label_callback'](['type' => 'text']);

        $this->assertSame('<p class="tl_error">foobar</p>', $label[1]);
    }
}
