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

use Contao\News;
use Contao\NewsBundle\EventListener\InsertTagsListener;
use Contao\NewsModel;
use Contao\TestCase\ContaoTestCase;
use Psr\Log\LoggerInterface;

class InsertTagsListenerTest extends ContaoTestCase
{
    public function testTriggersWarningForNewsFeedTag(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('The "news_feed" insert tag has been removed in Contao 5.0. Use "link_url" instead.')
        ;

        $listener = new InsertTagsListener($this->mockContaoFramework(), $logger);
        $url = $listener('news_feed::2', false, null, []);

        $this->assertFalse($url);
    }

    public function testReplacesTheNewsTags(): void
    {
        $newsModel = $this->mockClassWithProperties(NewsModel::class);
        $newsModel->headline = '"Foo" is not "bar"';
        $newsModel->teaser = '<p>Foo does not equal bar.</p>';

        $news = $this->mockAdapter(['generateNewsUrl']);
        $news
            ->method('generateNewsUrl')
            ->willReturnCallback(
                static function (NewsModel $model, bool $addArchive, bool $absolute): string {
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

        $logger = $this->createMock(LoggerInterface::class);
        $listener = new InsertTagsListener($this->mockContaoFramework($adapters), $logger);

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;">"Foo" is not "bar"</a>',
            $listener('news::2', false, null, [])
        );

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;" target="_blank" rel="noreferrer noopener">"Foo" is not "bar"</a>',
            $listener('news::2::blank', false, null, [])
        );

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;">',
            $listener('news_open::2', false, null, [])
        );

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;" target="_blank" rel="noreferrer noopener">',
            $listener('news_open::2::blank', false, null, [])
        );

        $this->assertSame(
            '<a href="http://domain.tld/news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;" target="_blank" rel="noreferrer noopener">',
            $listener('news_open::2::absolute::blank', false, null, [])
        );

        $this->assertSame(
            '<a href="http://domain.tld/news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;" target="_blank" rel="noreferrer noopener">',
            $listener('news_open::2::blank::absolute', false, null, [])
        );

        $this->assertSame(
            'news/foo-is-not-bar.html',
            $listener('news_url::2', false, null, [])
        );

        $this->assertSame(
            'http://domain.tld/news/foo-is-not-bar.html',
            $listener('news_url::2', false, null, ['absolute'])
        );

        $this->assertSame(
            'http://domain.tld/news/foo-is-not-bar.html',
            $listener('news_url::2::absolute', false, null, [])
        );

        $this->assertSame(
            'http://domain.tld/news/foo-is-not-bar.html',
            $listener('news_url::2::blank::absolute', false, null, [])
        );

        $this->assertSame(
            '&quot;Foo&quot; is not &quot;bar&quot;',
            $listener('news_title::2', false, null, [])
        );

        $this->assertSame(
            '<p>Foo does not equal bar.</p>',
            $listener('news_teaser::2', false, null, [])
        );
    }

    public function testHandlesEmptyUrls(): void
    {
        $newsModel = $this->mockClassWithProperties(NewsModel::class);
        $newsModel->headline = '"Foo" is not "bar"';
        $newsModel->teaser = '<p>Foo does not equal bar.</p>';

        $news = $this->mockAdapter(['generateNewsUrl']);
        $news
            ->method('generateNewsUrl')
            ->willReturn('')
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $newsModel]),
            News::class => $news,
        ];

        $logger = $this->createMock(LoggerInterface::class);
        $listener = new InsertTagsListener($this->mockContaoFramework($adapters), $logger);

        $this->assertSame(
            '<a href="./" title="&quot;Foo&quot; is not &quot;bar&quot;">"Foo" is not "bar"</a>',
            $listener('news::2', false, null, [])
        );

        $this->assertSame(
            '<a href="./" title="&quot;Foo&quot; is not &quot;bar&quot;">',
            $listener('news_open::2', false, null, [])
        );

        $this->assertSame(
            './',
            $listener('news_url::2', false, null, [])
        );
    }

    public function testReturnsFalseIfTheTagIsUnknown(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $listener = new InsertTagsListener($this->mockContaoFramework(), $logger);

        $this->assertFalse($listener('link_url::2', false, null, []));
    }

    public function testReturnsAnEmptyStringIfThereIsNoModel(): void
    {
        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => null]),
        ];

        $logger = $this->createMock(LoggerInterface::class);
        $listener = new InsertTagsListener($this->mockContaoFramework($adapters), $logger);

        $this->assertSame('', $listener('news_url::3', false, null, []));
    }
}
