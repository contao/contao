<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Input;
use Contao\News;
use Contao\NewsArchiveModel;
use Contao\NewsBundle\EventListener\BreadcrumbListener;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class BreadcrumbListenerTest extends ContaoTestCase
{
    protected function setUp(): void
    {
        $GLOBALS['objPage'] = $this->mockClassWithProperties(
            PageModel::class,
            [
                'id' => 4,
                'pageTitle' => 'News'
            ]
        );
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testDoesNotAddBreadcrumbItemForMissingPage(array $items): void
    {
        unset($GLOBALS['objPage']);

        $framework = $this->mockContaoFramework();
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $this->assertSame($items, $result);
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testDoesNotAddBreadcrumbItemForEnabledAutoItemButMissingAutoItemParameter(array $items): void
    {
        $inputAdapter = $this->mockAdapter(['get']);
        $inputAdapter
            ->method('get')
            ->with('auto_item')
            ->willReturn(null);

        $framework = $this->mockContaoFramework([Input::class => $inputAdapter]);
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $this->assertSame($items, $result);
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testDoesNotAddBreadcrumbItemForDisabledAutoItemAndMissingItemsParameter(array $items): void
    {
        $inputAdapter = $this->mockAdapter(['get']);
        $inputAdapter
            ->method('get')
            ->with('items')
            ->willReturn(null);

        $framework = $this->mockContaoFramework(
            [
                Input::class => $inputAdapter,
                Config::class => $this->mockConfigAdapter(false)
            ]
        );
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $this->assertSame($items, $result);
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testDoesNotAddBreadcrumbItemForNoneNewsArchiveWithJumpToPageSameAsPageId(array $items): void
    {
        $newsArchiveAdapter = $this->mockNewsArchiveAdapter(null);

        $framework = $this->mockContaoFramework([NewsArchiveModel::class => $newsArchiveAdapter]);
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $this->assertSame($items, $result);
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testDoesNotAddBreadcrumbItemForNotExistingNews(array $items): void
    {
        $newsModelAdapter = $this->mockNewsModelAdapter(null);

        $framework = $this->mockContaoFramework([NewsModel::class => $newsModelAdapter]);
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $this->assertSame($items, $result);
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testAddsBreadcrumbItemForNewsEntry(array $items): void
    {
        $framework = $this->mockContaoFramework();
        $listener = new BreadcrumbListener($framework);

        $expectedCount = \count($items) + 1;
        $items = $listener->onGenerateBreadcrumb($items);

        $this->assertCount($expectedCount, $items);
        $this->assertSame(
            [
                'isRoot' => false,
                'isActive' => true,
                'href' => 'news/foo-bar.html',
                'title' => 'Foo bar',
                'link' => 'Foo bar',
                'data' => [
                    'id' => 4,
                    'pageTitle' => 'News'
                ],
                'class' => '',
            ],
            $items[$expectedCount - 1]
        );
    }


    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testAddsBreadcrumbItemUsingPageTitleForNewsEntry(array $items): void
    {
        $newsModelAdapter = $this->mockNewsModelAdapter(
            $this->mockNewsModel(
                [
                    'headline' => 'Foo bar',
                    'pageTitle' => 'Custom page title'
                ]
            )
        );

        $framework = $this->mockContaoFramework([NewsModel::class => $newsModelAdapter]);
        $listener = new BreadcrumbListener($framework);

        $expectedCount = \count($items) + 1;
        $items = $listener->onGenerateBreadcrumb($items);

        $this->assertCount($expectedCount, $items);
        $this->assertSame(
            [
                'isRoot' => false,
                'isActive' => true,
                'href' => 'news/foo-bar.html',
                'title' => 'Custom page title',
                'link' => 'Custom page title',
                'data' => [
                    'id' => 4,
                    'pageTitle' => 'News'
                ],
                'class' => '',
            ],
            $items[$expectedCount - 1]
        );
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testOverridesCurrentPageItemWithNewsEntry(array $items): void
    {
        $GLOBALS['objPage'] = $this->mockClassWithProperties(
            PageModel::class,
            [
                'id' => 4,
                'pageTitle' => 'News',
                'requireItem' => '1'
            ]
        );

        $newsArchiveModel = $this->mockNewsArchiveModel();
        $newsArchiveAdapter = $this->mockNewsArchiveAdapter($newsArchiveModel);

        $newsModelAdapter = $this->mockNewsModelAdapter(
            $this->mockNewsModel(
                [
                    'headline' => 'Foo bar',
                    'pageTitle' => 'Custom page title'
                ]
            )
        );

        $framework = $this->mockContaoFramework(
            [
                NewsArchiveModel::class => $newsArchiveAdapter,
                NewsModel::class => $newsModelAdapter
            ]
        );
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $count = count($items);

        if ($count) {
            $items[$count -1]['title'] = 'Custom page title';
            $items[$count -1]['link'] = 'Custom page title';
            $items[$count -1]['href'] = 'news/foo-bar.html';
        }

        $this->assertSame($items, $result);
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testOverridesCurrentPageItemWithNewsEntryUsingPageTitle(array $items): void
    {
        $GLOBALS['objPage'] = $this->mockClassWithProperties(
            PageModel::class,
            [
                'id' => 4,
                'pageTitle' => 'News',
                'requireItem' => '1'
            ]
        );

        $newsArchiveModel = $this->mockNewsArchiveModel();
        $newsArchiveAdapter = $this->mockNewsArchiveAdapter($newsArchiveModel);

        $framework = $this->mockContaoFramework([NewsArchiveModel::class => $newsArchiveAdapter]);
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $count = count($items);

        if ($count) {
            $items[$count -1]['title'] = 'Foo bar';
            $items[$count -1]['link'] = 'Foo bar';
            $items[$count -1]['href'] = 'news/foo-bar.html';
        }

        $this->assertSame($items, $result);
    }

    protected function mockContaoFramework(array $adapters = []): ContaoFrameworkInterface
    {
        if (!isset($adapters[NewsArchiveModel::class])) {
            $newsArchiveModel = $this->mockNewsArchiveModel();
            $adapters[NewsArchiveModel::class] = $this->mockNewsArchiveAdapter($newsArchiveModel);
        }

        if (!isset($adapters[NewsModel::class])) {
            $newsModel = $this->mockNewsModel();
            $adapters[NewsModel::class] = $this->mockNewsModelAdapter($newsModel);
        }

        if (!isset($adapters[PageModel::class])) {
            $pageModel= $this->mockPageModel();
            $adapters[PageModel::class] = $this->mockPageModelAdapter($pageModel);
        }

        if (!isset($adapters[Config::class])) {
            $adapters[Config::class] = $this->mockConfigAdapter();
        }

        if (!isset($adapters[Input::class])) {
            $adapters[Input::class] = $this->mockInputAdapter();
        }

        if (!isset($adapters[News::class])) {
            $adapters[News::class] = $this->mockNewsAdapter();
        }

        return parent::mockContaoFramework($adapters);
    }

    /**
     * @return MockObject|NewsArchiveModel
     */
    private function mockNewsArchiveModel(): NewsArchiveModel
    {
        return $this->mockClassWithProperties(
            NewsArchiveModel::class,
            [
                'id' => 3,
            ]
        );
    }

    /**
     * @return MockObject|NewsModel
     */
    private function mockNewsModel(?array $properties = null): NewsModel
    {
        $properties = $properties ?: [
            'headline' => 'Foo bar',
            'pageTitle' => ''
        ];

        return $this->mockClassWithProperties(NewsModel::class, $properties);
    }

    /**
     * @return MockObject|PageModel
     */
    private function mockPageModel(): PageModel
    {
        $page = $this->mockClassWithProperties(
            PageModel::class,
            [
                'id' => 4,
                'pageTitle' => 'News'
            ]
        );

        $page->method('row')->willReturn(
            [
                'id' => 4,
                'pageTitle' => 'News'
            ]
        );

        return $page;
    }

    /**
     * @return Adapter|MockObject
     */
    private function mockNewsArchiveAdapter(?NewsArchiveModel $newsArchiveModel): Adapter
    {
        $newsArchiveAdapter = $this->mockAdapter(['findOneByJumpTo']);
        $newsArchiveAdapter
            ->method('findOneByJumpTo')
            ->with(4)
            ->willReturn($newsArchiveModel);

        return $newsArchiveAdapter;
    }

    /**
     * @return Adapter|MockObject
     */
    private function mockNewsModelAdapter(?NewsModel $newsModel): Adapter
    {
        $newsModelAdapter = $this->mockAdapter(['findPublishedByParentAndIdOrAlias']);
        $newsModelAdapter
            ->method('findPublishedByParentAndIdOrAlias')
            ->with('foo-bar', [3])
            ->willReturn($newsModel);

        return $newsModelAdapter;
    }

    /**
     * @return Adapter|MockObject
     */
    private function mockConfigAdapter(bool $useAutoItem = true): Adapter
    {
        $configAdapter = $this->mockAdapter(['get']);
        $configAdapter
            ->method('get')
            ->with('useAutoItem')
            ->willReturn($useAutoItem);

        return $configAdapter;
    }

    /**
     * @return Adapter|MockObject
     */
    private function mockInputAdapter(): Adapter
    {
        $inputAdapter = $this->mockAdapter(['get']);
        $inputAdapter
            ->method('get')
            ->with('auto_item')
            ->willReturn('foo-bar');

        return $inputAdapter;
    }

    /**
     * @return Adapter|MockObject
     */
    private function mockPageModelAdapter(PageModel $pageModel): Adapter
    {
        $pageModelAdapter = $this->mockAdapter(['findByPk']);
        $pageModelAdapter
            ->method('findByPk')
            ->with(4)
            ->willReturn($pageModel);

        return $pageModelAdapter;
    }

    /**
     * @return Adapter|MockObject
     */
    private function mockNewsAdapter(): Adapter
    {
        $newsAdapter = $this->mockAdapter(['generateNewsUrl']);
        $newsAdapter
            ->method('generateNewsUrl')
            ->willReturn('news/foo-bar.html');

        return $newsAdapter;
    }

    public function provideBreadcrumbItems(): array
    {
        return [
            [
                [],
            ],
            [
                [
                    [
                        'isRoot' => true,
                        'isActive' => true,
                        'href' => 'index.html',
                        'title' => 'Home',
                        'link' => 'Home',
                        'data' => [
                            'id' => 4,
                        ],
                        'class' => '',
                    ],
                ],
            ],
            [
                [
                    [
                        'isRoot' => true,
                        'isActive' => false,
                        'href' => 'index.html',
                        'title' => 'Home',
                        'link' => 'Home',
                        'data' => [
                            'id' => 1,
                        ],
                        'class' => '',
                    ],
                    [
                        'isRoot' => false,
                        'isActive' => true,
                        'href' => 'mews.html',
                        'title' => 'News',
                        'link' => 'News',
                        'data' => [
                            'id' => 4,
                            'pageTitle' => 'News'
                        ],
                        'class' => '',
                    ],
                ],
            ],
        ];
    }
}
