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

use Contao\FaqBundle\EventListener\InsertTagsListener;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;

class InsertTagsListenerTest extends ContaoTestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\FaqBundle\EventListener\InsertTagsListener', $listener);
    }

    public function testReplacesTheFaqTags(): void
    {
        $page = $this->createMock(PageModel::class);

        $page
            ->method('getFrontendUrl')
            ->willReturn('faq/what-does-foobar-mean.html')
        ;

        $page
            ->method('getAbsoluteUrl')
            ->willReturn('http://domain.tld/faq/what-does-foobar-mean.html')
        ;

        $categoryModel = $this->createMock(FaqCategoryModel::class);

        $categoryModel
            ->method('getRelated')
            ->willReturn($page)
        ;

        $properties = [
            'alias' => 'what-does-foobar-mean',
            'question' => 'What does "foobar" mean?',
        ];

        $faqModel = $this->mockClassWithProperties(FaqModel::class, $properties);

        $faqModel
            ->method('getRelated')
            ->willReturn($categoryModel)
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $faqModel]),
        ];

        $listener = new InsertTagsListener($this->mockContaoFramework($adapters));

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
            'http://domain.tld/faq/what-does-foobar-mean.html',
            $listener->onReplaceInsertTags('faq_url::2', false, null, ['absolute'])
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
        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => null]),
        ];

        $listener = new InsertTagsListener($this->mockContaoFramework($adapters));

        $this->assertSame('', $listener->onReplaceInsertTags('faq_url::2'));
    }

    public function testReturnsAnEmptyStringIfThereIsNoCategoryModel(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $faqModel
            ->method('getRelated')
            ->willReturn(null)
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $faqModel]),
        ];

        $listener = new InsertTagsListener($this->mockContaoFramework($adapters));

        $this->assertSame('', $listener->onReplaceInsertTags('faq_url::3'));
    }
}
