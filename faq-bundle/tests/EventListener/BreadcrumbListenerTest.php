<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Tests\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\Input;
use Contao\FaqBundle\EventListener\BreadcrumbListener;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class BreadcrumbListenerTest extends ContaoTestCase
{
    private const ROOT_PAGE_ID = 1;

    private const PAGE_ID = 4;

    private const FAQ_CATEGORY_ID = 3;

    private const FAQ_ALIAS = 'foo-bar';

    private const FAQ_QUESTION = 'Foo bar';

    private const FAQ_URL = 'faq/foo-bar.html';

    private const CURRENT_PAGE = [
        'id' => self::PAGE_ID,
        'pageTitle' => 'FAQs'
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
    public function testDoesNotAddBreadcrumbItemForNoneFaqCategoriesWithJumpToPageSameAsPageId(array $items): void
    {
        $faqCategoryAdapter = $this->mockFaqCategoryAdapter(null);

        $framework = $this->mockContaoFramework([FaqCategoryModel::class => $faqCategoryAdapter]);
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $this->assertSame($items, $result);
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testDoesNotAddBreadcrumbItemForNotExistingFaq(array $items): void
    {
        $faqModelAdapter = $this->mockFaqModelAdapter(null);

        $framework = $this->mockContaoFramework([FaqModel::class => $faqModelAdapter]);
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $this->assertSame($items, $result);
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testAddsBreadcrumbItemForFaq(array $items): void
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
                'href' => self::FAQ_URL,
                'title' => self::FAQ_QUESTION,
                'link' => self::FAQ_QUESTION,
                'data' => self::CURRENT_PAGE,
                'class' => '',
            ],
            $items[$expectedCount - 1]
        );
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testOverridesCurrentPageItemWithFaqEntry(array $items): void
    {
        $GLOBALS['objPage'] = $this->mockClassWithProperties(
            PageModel::class,
            [
                'id' => self::PAGE_ID,
                'pageTitle' => 'News',
                'requireItem' => '1'
            ]
        );

        $faqCategoryModel = $this->mockFaqCategoryModel();
        $faqCategoryAdapter = $this->mockFaqCategoryAdapter($faqCategoryModel);

        $framework = $this->mockContaoFramework([FaqCategoryModel::class => $faqCategoryAdapter]);
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $count = count($items);

        if ($count) {
            $items[$count -1]['title'] = self::FAQ_QUESTION;
            $items[$count -1]['link'] = self::FAQ_QUESTION;
            $items[$count -1]['href'] = self::FAQ_URL;
        }

        $this->assertSame($items, $result);
    }

    protected function mockContaoFramework(array $adapters = []): ContaoFrameworkInterface
    {
        if (!isset($adapters[FaqCategoryModel::class])) {
            $faqCategoryModel = $this->mockFaqCategoryModel();
            $adapters[FaqCategoryModel::class] = $this->mockFaqCategoryAdapter($faqCategoryModel);
        }

        if (!isset($adapters[FaqModel::class])) {
            $faqModel = $this->mockFaqModel();
            $adapters[FaqModel::class] = $this->mockFaqModelAdapter($faqModel);
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

        return parent::mockContaoFramework($adapters);
    }

    /**
     * @return MockObject|FaqCategoryModel
     */
    private function mockFaqCategoryModel(): FaqCategoryModel
    {
        return $this->mockClassWithProperties(
            FaqCategoryModel::class,
            [
                'id' => self::FAQ_CATEGORY_ID,
            ]
        );
    }

    /**
     * @return MockObject|FaqModel
     */
    private function mockFaqModel(): FaqModel
    {
        return $this->mockClassWithProperties(
            FaqModel::class,
            [
                'question' => self::FAQ_QUESTION,
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
        $page->method('getFrontendUrl')->willReturn(self::FAQ_URL);

        return $page;
    }

    /**
     * @return Adapter|MockObject
     */
    private function mockFaqCategoryAdapter(?FaqCategoryModel $faqCategoryModel): Adapter
    {
        $faqCategoryAdapter = $this->mockAdapter(['findOneByJumpTo']);
        $faqCategoryAdapter
            ->method('findOneByJumpTo')
            ->with(self::PAGE_ID)
            ->willReturn($faqCategoryModel);

        return $faqCategoryAdapter;
    }

    /**
     * @return Adapter|MockObject
     */
    private function mockFaqModelAdapter(?FaqModel $faqModel): Adapter
    {
        $faqModelAdapter = $this->mockAdapter(['findPublishedByParentAndIdOrAlias']);
        $faqModelAdapter
            ->method('findPublishedByParentAndIdOrAlias')
            ->with(self::FAQ_ALIAS, [self::FAQ_CATEGORY_ID])
            ->willReturn($faqModel);

        return $faqModelAdapter;
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
            ->willReturn(self::FAQ_ALIAS);

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
                        'href' => 'faq.html',
                        'title' => 'FAQs',
                        'link' => 'FAQs',
                        'data' => self::CURRENT_PAGE,
                        'class' => '',
                    ],
                ],
            ],
        ];
    }
}
