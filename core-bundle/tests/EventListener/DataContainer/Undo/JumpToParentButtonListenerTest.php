<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer\Undo;

use Contao\Backend;
use Contao\Controller;
use Contao\CoreBundle\EventListener\DataContainer\Undo\JumpToParentButtonListener;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\Image;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

class JumpToParentButtonListenerTest extends TestCase
{
    /**
     * @var Adapter<Image>&MockObject
     */
    private Adapter $imageAdapter;

    /**
     * @var TranslatorInterface&MockObject
     */
    private TranslatorInterface $translator;

    /**
     * @var ContaoFramework&MockObject
     */
    private ContaoFramework $framework;

    /**
     * @var Connection&MockObject
     */
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->imageAdapter = $this->mockAdapter(['getHtml']);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->framework = $this->mockContaoFramework([
            Backend::class => $this->mockAdapter(['addToUrl']),
            Controller::class => $this->mockAdapter(['loadLanguageFile', 'loadDataContainer']),
            Image::class => $this->imageAdapter,
        ]);

        $this->connection = $this->createMock(Connection::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_DCA'], $GLOBALS['BE_MOD']);
    }

    public function testRenderJumpToParentButtonForDynamicParentTable(): void
    {
        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('parent.svg')
            ->willReturn('<img src="parent.svg">')
        ;

        $this->connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_news WHERE id = :id', ['id' => 24])
            ->willReturn('1')
        ;

        $this->connection
            ->method('quoteIdentifier')
            ->with('tl_news')
            ->willReturn('tl_news')
        ;

        $translationsMap = [
            ['tl_undo.parent_modal', [], 'contao_tl_undo', null, 'Show origin of Content element ID 42'],
        ];

        $this->translator
            ->method('trans')
            ->willReturnMap($translationsMap)
        ;

        $row = $this->setupForDataSetWithDynamicParent();
        $listener = new JumpToParentButtonListener($this->framework, $this->connection, $this->translator);

        $this->assertSame(
            "<a href=\"\" title=\"Show origin of Content element ID 42\" onclick=\"Backend.openModalIframe({'title':'Show origin of Content element ID 42','url': this.href });return false\"><img src=\"parent.svg\"></a> ",
            $listener($row, '', 'jumpToParent', 'jumpToParent', 'parent.svg')
        );
    }

    public function testRenderJumpToParentButtonForDirectParent(): void
    {
        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('parent.svg')
            ->willReturn('<img src="parent.svg">')
        ;

        $this->connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_form WHERE id = :id', ['id' => 1])
            ->willReturn('1')
        ;

        $this->connection
            ->method('quoteIdentifier')
            ->with('tl_form')
            ->willReturn('tl_form')
        ;

        $translationsMap = [
            ['tl_undo.parent_modal', [], 'contao_tl_undo', null, 'Go to parent of tl_form_field ID 42'],
        ];

        $this->translator
            ->method('trans')
            ->willReturnMap($translationsMap)
        ;

        $row = $this->setupForDataSetWithDirectParent();
        $listener = new JumpToParentButtonListener($this->framework, $this->connection, $this->translator);

        $this->assertSame(
            "<a href=\"\" title=\"Go to parent of tl_form_field ID 42\" onclick=\"Backend.openModalIframe({'title':'Go to parent of tl_form_field ID 42','url': this.href });return false\"><img src=\"parent.svg\"></a> ",
            $listener($row, '', 'jumpToParent', 'jumpToParent', 'parent.svg')
        );
    }

    public function testRendersDisabledJumpToParentButtonWhenParentHasBeenDeleted(): void
    {
        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('parent--disabled.svg')
            ->willReturn('<img src="parent--disabled.svg">')
        ;

        $this->connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_news WHERE id = :id', ['id' => 24])
            ->willReturn('0')
        ;

        $this->connection
            ->method('quoteIdentifier')
            ->with('tl_news')
            ->willReturn('tl_news')
        ;

        $row = $this->setupForDataSetWithDynamicParent();

        $GLOBALS['TL_LANG']['tl_undo']['parent_modal'] = 'Show origin of %s ID %s';
        $GLOBALS['TL_DCA']['tl_content']['config']['dynamicPtable'] = true;

        $listener = new JumpToParentButtonListener($this->framework, $this->connection, $this->translator);

        $this->assertSame(
            '<img src="parent--disabled.svg"> ',
            $listener($row, '', 'jumpToParent', 'jumpToParent', 'parent.svg')
        );
    }

    public function testRendersDisabledJumpToParentButtonIfNoBackEndModuleWasFound(): void
    {
        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('parent--disabled.svg')
            ->willReturn('<img src="parent--disabled.svg">')
        ;

        $this->connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_form WHERE id = :id', ['id' => 1])
            ->willReturn('1')
        ;

        $this->connection
            ->method('quoteIdentifier')
            ->with('tl_form')
            ->willReturn('tl_form')
        ;

        $translationsMap = [
            ['tl_undo.parent_modal', [], 'contao_tl_undo', null, 'Go to parent of tl_form_field ID 42'],
        ];

        $this->translator
            ->method('trans')
            ->willReturnMap($translationsMap)
        ;

        $row = $this->setupForDataSetWithDirectParent();

        $GLOBALS['TL_LANG']['tl_undo']['parent_modal'] = 'Show origin of %s ID %s';

        // No back-end module for `tl_form`
        unset($GLOBALS['BE_MOD']['content']['form']);

        $listener = new JumpToParentButtonListener($this->framework, $this->connection, $this->translator);

        $this->assertSame('<img src="parent--disabled.svg"> ', $listener($row, '', '', '', 'parent.svg'));
    }

    public function testRendersDisabledJumpToParentButton(): void
    {
        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('parent--disabled.svg')
            ->willReturn('<img src="parent--disabled.svg">')
        ;

        $row = $this->setupForDataSetWithoutParent();
        $listener = new JumpToParentButtonListener($this->framework, $this->connection, $this->translator);

        $this->assertSame(
            '<img src="parent--disabled.svg"> ',
            $listener($row, '', 'jumpToParent', 'jumpToParent', 'parent.svg')
        );
    }

    private function setupForDataSetWithDynamicParent(): array
    {
        $GLOBALS['BE_MOD']['content']['news'] = [
            'tables' => ['tl_news_archive', 'tl_news'],
        ];

        $GLOBALS['TL_LANG']['tl_undo']['parent_modal'] = 'Show origin of %s ID %s';

        $GLOBALS['TL_DCA']['tl_content']['config']['dynamicPtable'] = true;
        $GLOBALS['TL_DCA']['tl_news']['list']['sorting']['mode'] = DataContainer::MODE_PARENT;

        return [
            'id' => 1,
            'fromTable' => 'tl_content',
            'data' => serialize([
                'tl_content' => [
                    [
                        'id' => 42,
                        'pid' => 24,
                        'ptable' => 'tl_news',
                    ],
                ],
                'tl_news' => [
                    [
                        'id' => 24,
                    ],
                ],
            ]),
        ];
    }

    private function setupForDataSetWithDirectParent(): array
    {
        $GLOBALS['BE_MOD']['content']['form'] = [
            'tables' => ['tl_form', 'tl_form_field'],
        ];

        $GLOBALS['TL_LANG']['tl_undo']['parent_modal'] = 'Show origin of %s ID %s';
        $GLOBALS['TL_DCA']['tl_form']['list']['sorting']['mode'] = DataContainer::MODE_SORTED;
        $GLOBALS['TL_DCA']['tl_form_field']['config']['ptable'] = 'tl_form';

        return [
            'id' => 1,
            'fromTable' => 'tl_form_field',
            'data' => serialize([
                'tl_form_field' => [
                    [
                        'id' => 42,
                        'pid' => 1,
                    ],
                ],
                'tl_form' => [
                    [
                        'id' => 1,
                    ],
                ],
            ]),
        ];
    }

    private function setupForDataSetWithoutParent(): array
    {
        $GLOBALS['TL_LANG']['tl_undo']['parent_modal'] = 'Show origin of %s ID %s';

        $GLOBALS['TL_DCA']['tl_form']['config'] = [];

        return [
            'id' => 1,
            'fromTable' => 'tl_form',
            'data' => serialize([
                'tl_form' => [
                    [
                        'id' => 42,
                    ],
                ],
            ]),
        ];
    }
}
