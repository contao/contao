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
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\UserModel;
use Twig\Environment;

class LabelListenerTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['BE_MOD'], $GLOBALS['TL_LANG']);

        parent::tearDown();
    }

    public function testRendersUndoLabel(): void
    {
        $row = $this->setupDataSet();

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

        $framework = $this->mockContaoFramework([
            Backend::class => $this->mockAdapter(['addToUrl']),
            Controller::class => $this->mockAdapter(['loadLanguageFile', 'loadDataContainer']),
            Image::class => $this->mockAdapter(['getHtml']),
            UserModel::class => $userModelAdapter,
        ]);

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->willReturn($row['preview'])
        ;

        $dc = $this->createMock(DC_Table::class);
        $listener = new LabelListener($framework, $twig);

        $this->assertSame('<result>', $listener($row, '', $dc));
    }

    public function testRendersUndoLabelForTabularRecords(): void
    {
        $row = $this->setupTabularDataSet();

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

        $framework = $this->mockContaoFramework([
            Backend::class => $this->mockAdapter(['addToUrl']),
            Controller::class => $this->mockAdapter(['loadLanguageFile', 'loadDataContainer']),
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
        $listener = new LabelListener($framework, $twig);

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

        $GLOBALS['TL_DCA']['tl_news']['fields']['headline'] = ['inputType' => 'text'];

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
            'preview' => '<result>',
        ];
    }

    private function setupTabularDataSet(): array
    {
        $GLOBALS['BE_MOD']['content']['members'] = [
            'tables' => ['tl_user'],
        ];

        $GLOBALS['TL_LANG']['tl_undo']['parent_modal'] = 'Show origin of %s ID %s';

        $GLOBALS['TL_DCA']['tl_user']['list'] = [
            'label' => [
                'showColumns' => true,
                'fields' => ['username'],
            ],
            'sorting' => [
                'mode' => DataContainer::MODE_SORTABLE,
            ],
        ];

        $GLOBALS['TL_DCA']['tl_user']['fields']['headline'] = ['inputType' => 'text'];

        return [
            'id' => 1,
            'fromTable' => 'tl_user',
            'pid' => 1,
            'data' => serialize([
                'tl_user' => [
                    [
                        'id' => 42,
                        'username' => 'k.jones',
                    ],
                ],
            ]),
            'preview' => serialize(['h.lewis']),
        ];
    }
}
