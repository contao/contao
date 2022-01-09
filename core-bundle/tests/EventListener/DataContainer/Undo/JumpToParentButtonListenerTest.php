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

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_DCA']);
    }

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

    public function testRenderJumpToParentButton(): void
    {
        $row = $this->setupForDataSetWithParent();

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
            ['tl_undo.parent_modal', [], 'contao_tl_undo', null, 'Show parent of Content element ID 42'],
        ];

        $this->translator
            ->method('trans')
            ->willReturnMap($translationsMap)
        ;

        $listener = new JumpToParentButtonListener($this->framework, $this->connection, $this->translator);
        $buttonHtml = $listener($row, '', 'jumpToParent', 'jumpToParent', 'parent.svg');

        $this->assertSame("<a href=\"\" title=\"Show parent of Content element ID 42\" onclick=\"Backend.openModalIframe({'title':'Show parent of Content element ID 42','url': this.href });return false\"><img src=\"parent.svg\"></a> ", $buttonHtml);
    }

    public function testRendersDisabledJumpToParentButtonWhenParentHasBeenDeleted(): void
    {
        $row = $this->setupForDataSetWithParent();

        $GLOBALS['TL_LANG']['tl_undo']['parent_modal'] = 'Show parent of %s ID %s';

        $GLOBALS['TL_DCA']['tl_content']['config']['dynamicPtable'] = true;

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('parent_.svg')
            ->willReturn('<img src="parent_.svg">')
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

        $listener = new JumpToParentButtonListener($this->framework, $this->connection, $this->translator);
        $buttonHtml = $listener($row, '', '', '', 'parent.svg');

        $this->assertSame('<img src="parent_.svg"> ', $buttonHtml);
    }

    public function testRendersDisabledJumpToParentButton(): void
    {
        $row = $this->setupForDataSetWithoutParent();

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('parent_.svg')
            ->willReturn('<img src="parent_.svg">')
        ;

        $listener = new JumpToParentButtonListener($this->framework, $this->connection, $this->translator);
        $buttonHtml = $listener($row);

        $this->assertSame('<img src="parent_.svg"> ', $buttonHtml);
    }

    private function setupForDataSetWithParent(): array
    {
        $GLOBALS['BE_MOD']['content']['news'] = [
            'tables' => ['tl_news_archive', 'tl_news'],
        ];

        $GLOBALS['TL_LANG']['tl_undo']['parent_modal'] = 'Show parent of %s ID %s';

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

    private function setupForDataSetWithoutParent(): array
    {
        $GLOBALS['TL_LANG']['tl_undo']['parent_modal'] = 'Show parent of %s ID %s';

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
