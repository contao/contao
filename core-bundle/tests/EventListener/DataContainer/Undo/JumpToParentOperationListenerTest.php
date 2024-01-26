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
use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\EventListener\DataContainer\Undo\JumpToParentOperationListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class JumpToParentOperationListenerTest extends TestCase
{
    private TranslatorInterface&MockObject $translator;

    private ContaoFramework&MockObject $framework;

    private Connection&MockObject $connection;

    private Security&MockObject $security;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->framework = $this->mockContaoFramework([
            Backend::class => $this->mockAdapter(['addToUrl']),
            Controller::class => $this->mockAdapter(['loadLanguageFile', 'loadDataContainer']),
        ]);

        $this->connection = $this->createMock(Connection::class);
        $this->connection
            ->method('quoteIdentifier')
            ->willReturnArgument(0)
        ;

        $this->security = $this->createMock(Security::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_DCA'], $GLOBALS['BE_MOD']);
    }

    public function testRenderJumpToParentButtonForDynamicParentTable(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_news WHERE id = :id', ['id' => 24])
            ->willReturn('1')
        ;

        $this->setTranslatorMessage('Show origin of Content element ID 42');

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::DC_PREFIX.'tl_content', $this->isInstanceOf(CreateAction::class))
            ->willReturn(true)
        ;

        $row = $this->setupForDataSetWithDynamicParent();
        $operation = new DataContainerOperation('jumpToParent', [], $row, $this->createMock(DataContainer::class));

        $listener = new JumpToParentOperationListener($this->framework, $this->connection, $this->translator, $this->security);
        $listener($operation);

        $this->assertSame('Show origin of Content element ID 42', $operation['title']);
        $this->assertSame(" onclick=\"Backend.openModalIframe({'title':'Show origin of Content element ID 42','url': this.href });return false\"", $operation['attributes']);
    }

    public function testRenderJumpToParentButtonForDirectParent(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_form WHERE id = :id', ['id' => 1])
            ->willReturn('1')
        ;

        $this->setTranslatorMessage('Go to parent of tl_form_field ID 42');

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::DC_PREFIX.'tl_form_field', $this->isInstanceOf(CreateAction::class))
            ->willReturn(true)
        ;

        $row = $this->setupForDataSetWithDirectParent();
        $operation = new DataContainerOperation('jumpToParent', [], $row, $this->createMock(DataContainer::class));

        $listener = new JumpToParentOperationListener($this->framework, $this->connection, $this->translator, $this->security);
        $listener($operation);

        $this->assertSame('Go to parent of tl_form_field ID 42', $operation['title']);
        $this->assertSame(" onclick=\"Backend.openModalIframe({'title':'Go to parent of tl_form_field ID 42','url': this.href });return false\"", $operation['attributes']);
    }

    public function testRendersDisabledJumpToParentButtonWhenParentHasBeenDeleted(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_news WHERE id = :id', ['id' => 24])
            ->willReturn('0')
        ;

        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $row = $this->setupForDataSetWithDynamicParent();

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn($row)
        ;

        $operation
            ->expects($this->once())
            ->method('disable')
        ;

        $GLOBALS['TL_DCA']['tl_content']['config']['dynamicPtable'] = true;

        $listener = new JumpToParentOperationListener($this->framework, $this->connection, $this->translator, $this->security);
        $listener($operation);
    }

    public function testRendersDisabledJumpToParentButtonIfNoBackEndModuleWasFound(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_form WHERE id = :id', ['id' => 1])
            ->willReturn('1')
        ;

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::DC_PREFIX.'tl_form_field', $this->isInstanceOf(CreateAction::class))
            ->willReturn(true)
        ;

        $this->setTranslatorMessage('Go to parent of tl_form_field ID 42');

        $row = $this->setupForDataSetWithDirectParent();

        // No back-end module for `tl_form`
        unset($GLOBALS['BE_MOD']['content']['form']);

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn($row)
        ;

        $operation
            ->expects($this->once())
            ->method('disable')
        ;

        $listener = new JumpToParentOperationListener($this->framework, $this->connection, $this->translator, $this->security);
        $listener($operation);
    }

    public function testRendersDisabledJumpToParentButton(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $row = $this->setupForDataSetWithoutParent();

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn($row)
        ;

        $operation
            ->expects($this->once())
            ->method('disable')
        ;

        $listener = new JumpToParentOperationListener($this->framework, $this->connection, $this->translator, $this->security);
        $listener($operation);
    }

    public function testDisablesButtonIfAccessToOriginalRecordIsNotGranted(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_form WHERE id = :id', ['id' => 1])
            ->willReturn('1')
        ;

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::DC_PREFIX.'tl_form_field', $this->isInstanceOf(CreateAction::class))
            ->willReturn(false)
        ;

        $row = $this->setupForDataSetWithDirectParent();

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn($row)
        ;

        $operation
            ->expects($this->once())
            ->method('disable')
        ;

        $listener = new JumpToParentOperationListener($this->framework, $this->connection, $this->translator, $this->security);
        $listener($operation);
    }

    private function setupForDataSetWithDynamicParent(): array
    {
        $GLOBALS['BE_MOD']['content']['news'] = [
            'tables' => ['tl_news_archive', 'tl_news'],
        ];

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

    private function setTranslatorMessage(string $message): void
    {
        $translationsMap = [
            ['tl_undo.parent_modal', [], 'contao_tl_undo', null, $message],
        ];

        $this->translator
            ->method('trans')
            ->willReturnMap($translationsMap)
        ;
    }
}
