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
use Contao\CoreBundle\EventListener\DataContainer\Undo\LabelListener;
use Contao\CoreBundle\Fixtures\Contao\DC_NewsTableStub;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\UserModel;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class LabelListenerTest extends TestCase
{
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_DCA']);
    }

    public function testRendersUndoLabel(): void
    {
        $connectionAdapter = $this->createMock(Connection::class);

        $userModelAdapter = $this->mockAdapter(['findById']);
        $userModel = $this->mockClassWithProperties(UserModel::class, [
            'id' => 1,
            'username' => 'k.jones',
        ]);

        $userModelAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($userModel)
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnMap([
                ['tl_undo.pid.0', [], 'contao_tl_undo', null, 'User'],
                ['tl_undo.fromTable.0', [], 'contao_tl_undo', null, 'Source table'],
                ['MSC.parent', [], 'contao_default', null, 'Parent'],
            ])
        ;

        $dataContainerAdapter = $this->mockAdapter(['getDriverForTable']);
        $dataContainerAdapter
            ->expects($this->once())
            ->method('getDriverForTable')
            ->with('tl_news')
            ->willReturn(DC_NewsTableStub::class)
        ;

        $framework = $this->mockContaoFramework([
            Backend::class => $this->mockAdapter(['addToUrl']),
            Controller::class => $this->mockAdapter(['loadLanguageFile', 'loadDataContainer']),
            DataContainer::class => $dataContainerAdapter,
            Image::class => $this->mockAdapter(['getHtml']),
            UserModel::class => $userModelAdapter,
        ]);

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->willReturn('<result>')
        ;

        $dc = $this->createMock(DC_Table::class);
        $row = $this->setupDataSet();

        $listener = new LabelListener($framework, $connectionAdapter, $translator, $twig);

        $this->assertSame('<result>', $listener($row, '', $dc));
    }

    private function setupDataSet(): array
    {
        $GLOBALS['BE_MOD']['content']['news'] = [
            'tables' => ['tl_news_archive', 'tl_news'],
        ];

        $GLOBALS['TL_LANG']['tl_undo']['parent_modal'] = 'Show origin of %s ID %s';

        $GLOBALS['TL_DCA']['tl_news']['list'] = [
            'label' => [
                'fields' => ['headline'],
                'format' => '%s',
            ],
            'sorting' => [
                'mode' => DataContainer::MODE_PARENT,
            ],
        ];

        $GLOBALS['TL_DCA']['tl_news']['fields']['headline'] = [
            'inputType' => 'text',
        ];

        return [
            'id' => 1,
            'fromTable' => 'tl_news',
            'pid' => 1,
            'data' => serialize([
                'tl_news' => [
                    [
                        'pid' => 1,
                        'id' => 42,
                        'headline' => 'Foo bar!',
                    ],
                ],
            ]),
        ];
    }
}
