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

use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\NewsBundle\EventListener\InsertTagsListener;
use Contao\NewsModel;
use Contao\TestCase\ContaoTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InsertTagsListenerTest extends ContaoTestCase
{
    public function testTriggersWarningForNewsFeedTag(): void
    {
        $urlGenerator = $this->createMock(ContentUrlGenerator::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('The "news_feed" insert tag has been removed in Contao 5.0. Use "link_url" instead.')
        ;

        $listener = new InsertTagsListener($this->mockContaoFramework(), $urlGenerator, $logger);
        $url = $listener('news_feed::2', false, null, []);

        $this->assertFalse($url);
    }

    public function testReplacesTheNewsTags(): void
    {
        $newsModel = $this->mockClassWithProperties(NewsModel::class);
        $newsModel->headline = '"Foo" is not "bar"';
        $newsModel->teaser = '<p>Foo does not equal bar.</p>';

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $newsModel]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->exactly(10))
            ->method('generate')
            ->withConsecutive(
                [$newsModel, [], UrlGeneratorInterface::ABSOLUTE_PATH],
                [$newsModel, [], UrlGeneratorInterface::ABSOLUTE_PATH],
                [$newsModel, [], UrlGeneratorInterface::ABSOLUTE_PATH],
                [$newsModel, [], UrlGeneratorInterface::ABSOLUTE_PATH],
                [$newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL],
                [$newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL],
                [$newsModel, [], UrlGeneratorInterface::ABSOLUTE_PATH],
                [$newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL],
                [$newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL],
                [$newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL],
            )
            ->willReturnOnConsecutiveCalls(
                'news/foo-is-not-bar.html',
                'news/foo-is-not-bar.html',
                'news/foo-is-not-bar.html',
                'news/foo-is-not-bar.html',
                'http://domain.tld/news/foo-is-not-bar.html',
                'http://domain.tld/news/foo-is-not-bar.html',
                'news/foo-is-not-bar.html',
                'http://domain.tld/news/foo-is-not-bar.html',
                'http://domain.tld/news/foo-is-not-bar.html',
                'http://domain.tld/news/foo-is-not-bar.html',
            )
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $listener = new InsertTagsListener($this->mockContaoFramework($adapters), $urlGenerator, $logger);

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;">"Foo" is not "bar"</a>',
            $listener('news::2', false, null, []),
        );

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;" target="_blank" rel="noreferrer noopener">"Foo" is not "bar"</a>',
            $listener('news::2::blank', false, null, []),
        );

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;">',
            $listener('news_open::2', false, null, []),
        );

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;" target="_blank" rel="noreferrer noopener">',
            $listener('news_open::2::blank', false, null, []),
        );

        $this->assertSame(
            '<a href="http://domain.tld/news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;" target="_blank" rel="noreferrer noopener">',
            $listener('news_open::2::absolute::blank', false, null, []),
        );

        $this->assertSame(
            '<a href="http://domain.tld/news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;" target="_blank" rel="noreferrer noopener">',
            $listener('news_open::2::blank::absolute', false, null, []),
        );

        $this->assertSame(
            'news/foo-is-not-bar.html',
            $listener('news_url::2', false, null, []),
        );

        $this->assertSame(
            'http://domain.tld/news/foo-is-not-bar.html',
            $listener('news_url::2', false, null, ['absolute']),
        );

        $this->assertSame(
            'http://domain.tld/news/foo-is-not-bar.html',
            $listener('news_url::2::absolute', false, null, []),
        );

        $this->assertSame(
            'http://domain.tld/news/foo-is-not-bar.html',
            $listener('news_url::2::blank::absolute', false, null, []),
        );

        $this->assertSame(
            '&quot;Foo&quot; is not &quot;bar&quot;',
            $listener('news_title::2', false, null, []),
        );

        $this->assertSame(
            '<p>Foo does not equal bar.</p>',
            $listener('news_teaser::2', false, null, []),
        );
    }

    public function testReturnsFalseIfTheTagIsUnknown(): void
    {
        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $logger = $this->createMock(LoggerInterface::class);

        $listener = new InsertTagsListener($this->mockContaoFramework(), $urlGenerator, $logger);

        $this->assertFalse($listener('link_url::2', false, null, []));
    }

    public function testReturnsAnEmptyStringIfThereIsNoModel(): void
    {
        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => null]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $logger = $this->createMock(LoggerInterface::class);
        $listener = new InsertTagsListener($this->mockContaoFramework($adapters), $urlGenerator, $logger);

        $this->assertSame('', $listener('news_url::3', false, null, []));
    }
}
