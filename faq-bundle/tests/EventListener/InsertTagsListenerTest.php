<?php

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
use Contao\FaqBundle\EventListener\InsertTagsListener;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\PageModel;
use PHPUnit\Framework\TestCase;

/**
 * Tests the InsertTagsListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InsertTagsListenerTest extends TestCase
{
    /**
     * Tests that the listener returns a replacement string.
     */
    public function testReplacesTheFaqTags()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertSame(
            '<a href="faq/what-does-foobar-mean.html" title="What does &quot;foobar&quot; mean?">What does "foobar" mean?</a>',
            $listener->onReplaceInsertTags('faq::2')
        );

        $this->assertSame(
            '<a href="faq/what-does-foobar-mean.html" title="What does &quot;foobar&quot; mean?">',
            $listener->onReplaceInsertTags('faq_open::2')
        );

        $this->assertSame(
            'faq/what-does-foobar-mean.html',
            $listener->onReplaceInsertTags('faq_url::2')
        );

        $this->assertSame(
            'What does &quot;foobar&quot; mean?',
            $listener->onReplaceInsertTags('faq_title::2')
        );
    }

    /**
     * Tests that the listener returns false if the tag is unknown.
     */
    public function testReturnsFalseIfTheTagIsUnknown()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertFalse($listener->onReplaceInsertTags('link_url::2'));
    }

    /**
     * Tests that the listener returns an empty string if there is no model.
     */
    public function testReturnsAnEmptyStringIfThereIsNoModel()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework(true));

        $this->assertSame('', $listener->onReplaceInsertTags('faq_url::2'));
    }

    /**
     * Tests that the listener returns an empty string if there is no category model.
     */
    public function testReturnsAnEmptyStringIfThereIsNoCategoryModel()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework(false, true));

        $this->assertSame('', $listener->onReplaceInsertTags('faq_url::3'));
    }

    /**
     * Returns a ContaoFramework instance.
     *
     * @param bool $noFaqModel
     * @param bool $noFaqCategory
     *
     * @return ContaoFrameworkInterface
     */
    private function mockContaoFramework($noFaqModel = false, $noFaqCategory = false)
    {
        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $page = $this->createMock(PageModel::class);

        $page
            ->method('getFrontendUrl')
            ->willReturn('faq/what-does-foobar-mean.html')
        ;

        $category = $this->createMock(FaqCategoryModel::class);

        $category
            ->method('getRelated')
            ->willReturn($page)
        ;

        $faq = $this->createMock(FaqModel::class);

        $faq
            ->method('getRelated')
            ->willReturn($noFaqCategory ? null : $category)
        ;

        $faq
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'id':
                        return 2;

                    case 'alias':
                        return 'what-does-foobar-mean';

                    case 'question':
                        return 'What does "foobar" mean?';

                    default:
                        return null;
                }
            })
        ;

        $faqModelAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByIdOrAlias'])
            ->getMock()
        ;

        $faqModelAdapter
            ->method('findByIdOrAlias')
            ->willReturn($noFaqModel ? null : $faq)
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($faqModelAdapter) {
                switch ($key) {
                    case FaqModel::class:
                        return $faqModelAdapter;

                    case Config::class:
                        return $this->mockConfigAdapter();

                    default:
                        return null;
                }
            })
        ;

        return $framework;
    }

    /**
     * Mocks a config adapter.
     *
     * @return Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockConfigAdapter()
    {
        $configAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['isComplete', 'preload', 'getInstance', 'get'])
            ->getMock()
        ;

        $configAdapter
            ->method('isComplete')
            ->willReturn(true)
        ;

        $configAdapter
            ->method('preload')
            ->willReturn(null)
        ;

        $configAdapter
            ->method('getInstance')
            ->willReturn(null)
        ;

        $configAdapter
            ->method('get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'characterSet':
                        return 'UTF-8';

                    case 'useAutoItem':
                        return true;

                    default:
                        return null;
                }
            })
        ;

        return $configAdapter;
    }
}
