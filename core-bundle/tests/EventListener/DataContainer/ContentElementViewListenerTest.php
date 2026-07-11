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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DC_Table;
use Contao\Image;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

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

        $listener = $this->createContentElementViewListener();
        $listener->adjustListView($dc);

        $this->assertIsArray($GLOBALS['TL_DCA']['tl_content']['list']['sorting']);
    }

    public function testDoesNotAdjustOtherView(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['list']['sorting'] = 'foobar';

        $dc = $this->createClassWithPropertiesStub(DC_Table::class, [
            'parentTable' => 'tl_article',
        ]);

        $listener = $this->createContentElementViewListener();
        $listener->adjustListView($dc);

        $this->assertSame('foobar', $GLOBALS['TL_DCA']['tl_content']['list']['sorting']);
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

        $listener = $this->createContentElementViewListener($framework, $translator);
        $label = $listener->generateLabel($row, '', $dc);

        $this->assertSame($expectedLabel, $label->htmlLabel);
        $this->assertSame($expectedPreview, $label->htmlPreview);
        $this->assertSame($expectedClass, $label->state);
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

        $listener = $this->createContentElementViewListener($framework, $translator);
        $label = $listener->generateLabel(['type' => 'text'], '', $dc);

        $this->assertSame('<p class="tl_error">foobar</p>', $label->htmlPreview);
    }

    private function createContentElementViewListener(ContaoFramework|null $framework = null, TranslatorInterface|null $translator = null): ContentElementViewListener
    {
        $framework ??= $this->createContaoFrameworkStub();
        $translator ??= $this->createStub(TranslatorInterface::class);

        $twig = new Environment($this->createStub(LoaderInterface::class));
        $twig->addExtension(new TranslationExtension($translator));

        return new ContentElementViewListener($framework, $translator, $twig);
    }
}
