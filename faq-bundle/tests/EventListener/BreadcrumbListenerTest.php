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
    private const CURRENT_PAGE = [
        'id' => 4,
        'pageTitle' => 'FAQs'
    ];

    protected function setUp(): void
    {
        $GLOBALS['objPage'] = $this->mockClassWithProperties(
            PageModel::class,
            [
                'id' => 4,
                'pageTitle' => 'FAQs'
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
                'href' => 'faq/foo-bar.html',
                'title' => 'Foo bar',
                'link' => 'Foo bar',
                'data' => [
                    'id' => 4,
                    'pageTitle' => 'FAQs'
                ],
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
                'id' => 4,
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
            $items[$count -1]['title'] = 'Foo bar';
            $items[$count -1]['link'] = 'Foo bar';
            $items[$count -1]['href'] = 'faq/foo-bar.html';
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
                'id' => 3,
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
                'question' => 'Foo bar',
            ]
        );
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
                'pageTitle' => 'FAQs'
            ]
        );
        $page->method('row')->willReturn(
            [
                'id' => 4,
                'pageTitle' => 'FAQs'
            ]
        );
        $page->method('getFrontendUrl')->willReturn('faq/foo-bar.html');

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
            ->with(4)
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
            ->with('foo-bar', [3])
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
                        'href' => 'faq.html',
                        'title' => 'FAQs',
                        'link' => 'FAQs',
                        'data' => [
                            'id' => 4,
                            'pageTitle' => 'FAQs'
                        ],
                        'class' => '',
                    ],
                ],
            ],
        ];
    }
}
