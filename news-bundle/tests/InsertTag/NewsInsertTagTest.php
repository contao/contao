<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\InsertTag;

use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\NewsBundle\InsertTag\NewsInsertTag;
use Contao\NewsModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NewsInsertTagTest extends ContaoTestCase
{
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

        $listener = new NewsInsertTag($this->mockContaoFramework($adapters), $urlGenerator);

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;">"Foo" is not "bar"</a>',
            $listener(new ResolvedInsertTag('news', new ResolvedParameters(['2']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;" target="_blank" rel="noreferrer noopener">"Foo" is not "bar"</a>',
            $listener(new ResolvedInsertTag('news', new ResolvedParameters(['2', 'blank']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;">',
            $listener(new ResolvedInsertTag('news_open', new ResolvedParameters(['2']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;" target="_blank" rel="noreferrer noopener">',
            $listener(new ResolvedInsertTag('news_open', new ResolvedParameters(['2', 'blank']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="http://domain.tld/news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;" target="_blank" rel="noreferrer noopener">',
            $listener(new ResolvedInsertTag('news_open', new ResolvedParameters(['2', 'absolute', 'blank']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="http://domain.tld/news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;" target="_blank" rel="noreferrer noopener">',
            $listener(new ResolvedInsertTag('news_open', new ResolvedParameters(['2', 'blank', 'absolute']), []))->getValue(),
        );

        $this->assertSame(
            'news/foo-is-not-bar.html',
            $listener(new ResolvedInsertTag('news_url', new ResolvedParameters(['2']), []))->getValue(),
        );

        $this->assertSame(
            'http://domain.tld/news/foo-is-not-bar.html',
            $listener(new ResolvedInsertTag('news_url', new ResolvedParameters(['2', 'absolute']), []))->getValue(),
        );

        $this->assertSame(
            'http://domain.tld/news/foo-is-not-bar.html',
            $listener(new ResolvedInsertTag('news_url', new ResolvedParameters(['2', 'absolute']), []))->getValue(),
        );

        $this->assertSame(
            'http://domain.tld/news/foo-is-not-bar.html',
            $listener(new ResolvedInsertTag('news_url', new ResolvedParameters(['2', 'blank', 'absolute']), []))->getValue(),
        );

        $this->assertEquals(
            new InsertTagResult('"Foo" is not "bar"'),
            $listener(new ResolvedInsertTag('news_title', new ResolvedParameters(['2']), [])),
        );

        $this->assertSame(
            '<p>Foo does not equal bar.</p>',
            $listener(new ResolvedInsertTag('news_teaser', new ResolvedParameters(['2']), []))->getValue(),
        );
    }

    public function testReturnsAnEmptyStringIfThereIsNoModel(): void
    {
        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => null]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $listener = new NewsInsertTag($this->mockContaoFramework($adapters), $urlGenerator);

        $this->assertSame('', $listener(new ResolvedInsertTag('news_url', new ResolvedParameters(['3']), []))->getValue());
    }

    public function testReturnsAnEmptyUrlIfTheUrlGeneratorThrowsException(): void
    {
        $newsModel = $this->mockClassWithProperties(NewsModel::class);

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $newsModel]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->method('generate')
            ->willThrowException(new ForwardPageNotFoundException())
        ;

        $listener = new NewsInsertTag($this->mockContaoFramework($adapters), $urlGenerator);

        $this->assertSame('', $listener(new ResolvedInsertTag('news_url', new ResolvedParameters(['4']), []))->getValue());
    }
}
