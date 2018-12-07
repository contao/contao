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

final class BreadcrumbListenerTest extends ContaoTestCase
{
    private const ROOT_PAGE_ID = 1;

    private const PAGE_ID = 4;

    private const NEWS_ARCHIVE_ID = 3;

    private const NEWS_ALIAS = 'foo-bar';

    private const NEWS_HEADLINE = 'Foo bar';

    private const NEWS_URL = 'news/foo-bar.html';

    private const CURRENT_PAGE = [
        'id' => self::PAGE_ID,
        'pageTitle' => 'News'
    ];

    protected function setUp(): void
    {
        $GLOBALS['objPage'] = $this->mockClassWithProperties(PageModel::class, self::CURRENT_PAGE);
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
                'href' => self::NEWS_URL,
                'title' => self::NEWS_HEADLINE,
                'link' => self::NEWS_HEADLINE,
                'data' => self::CURRENT_PAGE,
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
                'id' => self::PAGE_ID,
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
            $items[$count -1]['title'] = self::NEWS_HEADLINE;
            $items[$count -1]['link'] = self::NEWS_HEADLINE;
            $items[$count -1]['href'] = self::NEWS_URL;
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
                'id' => self::NEWS_ARCHIVE_ID,
            ]
        );
    }

    /**
     * @return MockObject|NewsModel
     */
    private function mockNewsModel(): NewsModel
    {
        return $this->mockClassWithProperties(
            NewsModel::class,
            [
                'headline' => self::NEWS_HEADLINE,
            ]
        );
    }

    /**
     * @return MockObject|PageModel
     */
    private function mockPageModel(): PageModel
    {
        $page = $this->mockClassWithProperties(PageModel::class, self::CURRENT_PAGE);
        $page->method('row')->willReturn(self::CURRENT_PAGE);

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
            ->with(self::PAGE_ID)
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
            ->with(self::NEWS_ALIAS, [self::NEWS_ARCHIVE_ID])
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
            ->willReturn(self::NEWS_ALIAS);

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
            ->with(self::PAGE_ID)
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
            ->willReturn(self::NEWS_URL);

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
                            'id' => self::PAGE_ID,
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
                            'id' => self::ROOT_PAGE_ID,
                        ],
                        'class' => '',
                    ],
                    [
                        'isRoot' => false,
                        'isActive' => true,
                        'href' => 'mews.html',
                        'title' => 'News',
                        'link' => 'News',
                        'data' => self::CURRENT_PAGE,
                        'class' => '',
                    ],
                ],
            ],
        ];
    }
}
