<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Tests\EventListener;

use Contao\News;
use Contao\NewsBundle\EventListener\InsertTagsListener;
use Contao\NewsFeedModel;
use Contao\NewsModel;
use Contao\TestCase\ContaoTestCase;

class InsertTagsListenerTest extends ContaoTestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\NewsBundle\EventListener\InsertTagsListener', $listener);
    }

    public function testReplacesTheNewsFeedTag(): void
    {
        $properties = [
            'feedBase' => 'http://localhost/',
            'alias' => 'news',
        ];

        $feedModel = $this->mockClassWithProperties(NewsFeedModel::class, $properties);

        $adapters = [
            NewsFeedModel::class => $this->mockConfiguredAdapter(['findByPk' => $feedModel]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $listener = new InsertTagsListener($framework);

        $this->assertSame('http://localhost/share/news.xml', $listener->onReplaceInsertTags('news_feed::2'));
    }

    public function testReplacesTheNewsTags(): void
    {
        $properties = [
            'headline' => '"Foo" is not "bar"',
            'teaser' => '<p>Foo does not equal bar.</p>',
        ];

        $newsModel = $this->mockClassWithProperties(NewsModel::class, $properties);
        $news = $this->mockAdapter(['generateNewsUrl']);

        $news
            ->method('generateNewsUrl')
            ->willReturnCallback(
                function (NewsModel $model, bool $addArchive, bool $absolute): string {
                    if ($absolute) {
                        return 'http://domain.tld/news/foo-is-not-bar.html';
                    }

                    return 'news/foo-is-not-bar.html';
                }
            )
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $newsModel]),
            News::class => $news,
        ];

        $listener = new InsertTagsListener($this->mockContaoFramework($adapters));

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;">"Foo" is not "bar"</a>',
            $listener->onReplaceInsertTags('news::2')
        );

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;">',
            $listener->onReplaceInsertTags('news_open::2')
        );

        $this->assertSame(
            'news/foo-is-not-bar.html',
            $listener->onReplaceInsertTags('news_url::2')
        );

        $this->assertSame(
            'http://domain.tld/news/foo-is-not-bar.html',
            $listener->onReplaceInsertTags('news_url::2', false, null, ['absolute'])
        );

        $this->assertSame(
            '&quot;Foo&quot; is not &quot;bar&quot;',
            $listener->onReplaceInsertTags('news_title::2')
        );

        $this->assertSame(
            '<p>Foo does not equal bar.</p>',
            $listener->onReplaceInsertTags('news_teaser::2')
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
            NewsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => null]),
            NewsFeedModel::class => $this->mockConfiguredAdapter(['findByPk' => null]),
        ];

        $listener = new InsertTagsListener($this->mockContaoFramework($adapters));

        $this->assertSame('', $listener->onReplaceInsertTags('news_feed::3'));
        $this->assertSame('', $listener->onReplaceInsertTags('news_url::3'));
    }
}
