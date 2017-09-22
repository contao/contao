<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
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

class InsertTagsListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\FaqBundle\EventListener\InsertTagsListener', $listener);
    }

    public function testReplacesTheFaqTags(): void
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

    public function testReturnsFalseIfTheTagIsUnknown(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertFalse($listener->onReplaceInsertTags('link_url::2'));
    }

    public function testReturnsAnEmptyStringIfThereIsNoModel(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework(true));

        $this->assertSame('', $listener->onReplaceInsertTags('faq_url::2'));
    }

    public function testReturnsAnEmptyStringIfThereIsNoCategoryModel(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework(false, true));

        $this->assertSame('', $listener->onReplaceInsertTags('faq_url::3'));
    }

    /**
     * Mocks the Contao framework.
     *
     * @param bool $noFaqModel
     * @param bool $noFaqCategory
     *
     * @return ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockContaoFramework(bool $noFaqModel = false, bool $noFaqCategory = false): ContaoFrameworkInterface
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
            ->willReturnCallback(
                function (string $key) {
                    switch ($key) {
                        case 'id':
                            return 2;

                        case 'alias':
                            return 'what-does-foobar-mean';

                        case 'question':
                            return 'What does "foobar" mean?';
                    }

                    return null;
                }
            )
        ;

        $faqModelAdapter = $this->createMock(Adapter::class);

        $faqModelAdapter
            ->method('__call')
            ->willReturn($noFaqModel ? null : $faq)
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(
                function (string $key) use ($faqModelAdapter): ?Adapter {
                    switch ($key) {
                        case FaqModel::class:
                            return $faqModelAdapter;

                        case Config::class:
                            return $this->mockConfigAdapter();
                    }

                    return null;
                }
            )
        ;

        return $framework;
    }

    /**
     * Mocks a config adapter.
     *
     * @return Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockConfigAdapter(): Adapter
    {
        $configAdapter = $this->createMock(Adapter::class);

        $configAdapter
            ->method('__call')
            ->willReturnCallback(
                function (string $key, array $params) {
                    if ('get' === $key) {
                        switch ($params[0]) {
                            case 'characterSet':
                                return 'UTF-8';

                            case 'useAutoItem':
                                return true;
                        }
                    }

                    return null;
                }
            )
        ;

        return $configAdapter;
    }
}
