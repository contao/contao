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

        $faqModel = $this->mockClassWithProperties(FaqModel::class);
        $faqModel->alias = 'what-does-foobar-mean';
        $faqModel->question = 'What does "foobar" mean?';

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
            $listener->onReplaceInsertTags('faq::2', false, null, []),
        );

        $this->assertSame(
            '<a href="faq/what-does-foobar-mean.html" title="What does &quot;foobar&quot; mean?" target="_blank" rel="noreferrer noopener">What does "foobar" mean?</a>',
            $listener->onReplaceInsertTags('faq::2::blank', false, null, []),
        );

        $this->assertSame(
            '<a href="faq/what-does-foobar-mean.html" title="What does &quot;foobar&quot; mean?">',
            $listener->onReplaceInsertTags('faq_open::2', false, null, []),
        );

        $this->assertSame(
            '<a href="faq/what-does-foobar-mean.html" title="What does &quot;foobar&quot; mean?" target="_blank" rel="noreferrer noopener">',
            $listener->onReplaceInsertTags('faq_open::2::blank', false, null, []),
        );

        $this->assertSame(
            '<a href="http://domain.tld/faq/what-does-foobar-mean.html" title="What does &quot;foobar&quot; mean?" target="_blank" rel="noreferrer noopener">',
            $listener->onReplaceInsertTags('faq_open::2::blank::absolute', false, null, []),
        );

        $this->assertSame(
            '<a href="http://domain.tld/faq/what-does-foobar-mean.html" title="What does &quot;foobar&quot; mean?" target="_blank" rel="noreferrer noopener">',
            $listener->onReplaceInsertTags('faq_open::2::absolute::blank', false, null, []),
        );

        $this->assertSame(
            'faq/what-does-foobar-mean.html',
            $listener->onReplaceInsertTags('faq_url::2', false, null, []),
        );

        $this->assertSame(
            'http://domain.tld/faq/what-does-foobar-mean.html',
            $listener->onReplaceInsertTags('faq_url::2', false, null, ['absolute']),
        );

        $this->assertSame(
            'http://domain.tld/faq/what-does-foobar-mean.html',
            $listener->onReplaceInsertTags('faq_url::2::absolute', false, null, []),
        );

        $this->assertSame(
            'http://domain.tld/faq/what-does-foobar-mean.html',
            $listener->onReplaceInsertTags('faq_url::2::blank::absolute', false, null, []),
        );

        $this->assertSame(
            'What does &quot;foobar&quot; mean?',
            $listener->onReplaceInsertTags('faq_title::2', false, null, []),
        );
    }

    public function testHandlesEmptyUrls(): void
    {
        $page = $this->createMock(PageModel::class);
        $page
            ->method('getFrontendUrl')
            ->willReturn('')
        ;

        $categoryModel = $this->createMock(FaqCategoryModel::class);
        $categoryModel
            ->method('getRelated')
            ->willReturn($page)
        ;

        $faqModel = $this->mockClassWithProperties(FaqModel::class);
        $faqModel->alias = 'what-does-foobar-mean';
        $faqModel->question = 'What does "foobar" mean?';

        $faqModel
            ->method('getRelated')
            ->willReturn($categoryModel)
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $faqModel]),
        ];

        $listener = new InsertTagsListener($this->mockContaoFramework($adapters));

        $this->assertSame(
            '<a href="./" title="What does &quot;foobar&quot; mean?">What does "foobar" mean?</a>',
            $listener->onReplaceInsertTags('faq::2', false, null, []),
        );

        $this->assertSame(
            '<a href="./" title="What does &quot;foobar&quot; mean?">',
            $listener->onReplaceInsertTags('faq_open::2', false, null, []),
        );

        $this->assertSame(
            './',
            $listener->onReplaceInsertTags('faq_url::2', false, null, []),
        );
    }

    public function testReturnsFalseIfTheTagIsUnknown(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertFalse($listener->onReplaceInsertTags('link_url::2', false, null, []));
    }

    public function testReturnsAnEmptyStringIfThereIsNoModel(): void
    {
        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => null]),
        ];

        $listener = new InsertTagsListener($this->mockContaoFramework($adapters));

        $this->assertSame('', $listener->onReplaceInsertTags('faq_url::2', false, null, []));
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

        $this->assertSame('', $listener->onReplaceInsertTags('faq_url::3', false, null, []));
    }
}
