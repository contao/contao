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
        $matcher = $this->exactly(10);
        $urlGenerator
            ->expects($matcher)
            ->method('generate')
            ->willReturnCallback(
                function (...$parameters) use ($matcher, $newsModel) {
                    if (1 === $matcher->numberOfInvocations()) {
                        $this->assertSame($newsModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_PATH, $parameters[2]);

                        return 'news/foo-is-not-bar.html';
                    }
                    if (2 === $matcher->numberOfInvocations()) {
                        $this->assertSame($newsModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_PATH, $parameters[2]);

                        return 'news/foo-is-not-bar.html';
                    }
                    if (3 === $matcher->numberOfInvocations()) {
                        $this->assertSame($newsModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_PATH, $parameters[2]);

                        return 'news/foo-is-not-bar.html';
                    }
                    if (4 === $matcher->numberOfInvocations()) {
                        $this->assertSame($newsModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_PATH, $parameters[2]);

                        return 'news/foo-is-not-bar.html';
                    }
                    if (5 === $matcher->numberOfInvocations()) {
                        $this->assertSame($newsModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_URL, $parameters[2]);

                        return 'http://domain.tld/news/foo-is-not-bar.html';
                    }
                    if (6 === $matcher->numberOfInvocations()) {
                        $this->assertSame($newsModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_URL, $parameters[2]);

                        return 'http://domain.tld/news/foo-is-not-bar.html';
                    }
                    if (7 === $matcher->numberOfInvocations()) {
                        $this->assertSame($newsModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_PATH, $parameters[2]);

                        return 'news/foo-is-not-bar.html';
                    }
                    if (8 === $matcher->numberOfInvocations()) {
                        $this->assertSame($newsModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_URL, $parameters[2]);

                        return 'http://domain.tld/news/foo-is-not-bar.html';
                    }
                    if (9 === $matcher->numberOfInvocations()) {
                        $this->assertSame($newsModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_URL, $parameters[2]);

                        return 'http://domain.tld/news/foo-is-not-bar.html';
                    }
                    if (10 === $matcher->numberOfInvocations()) {
                        $this->assertSame($newsModel, $parameters[0]);
                        $this->assertSame([], $parameters[1]);
                        $this->assertSame(UrlGeneratorInterface::ABSOLUTE_URL, $parameters[2]);

                        return 'http://domain.tld/news/foo-is-not-bar.html';
                    }
                },
            )
        ;

        $listener = new NewsInsertTag($this->mockContaoFramework($adapters), $urlGenerator);

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html">"Foo" is not "bar"</a>',
            $listener(new ResolvedInsertTag('news', new ResolvedParameters(['2']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" target="_blank" rel="noreferrer noopener">"Foo" is not "bar"</a>',
            $listener(new ResolvedInsertTag('news', new ResolvedParameters(['2', 'blank']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html">',
            $listener(new ResolvedInsertTag('news_open', new ResolvedParameters(['2']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" target="_blank" rel="noreferrer noopener">',
            $listener(new ResolvedInsertTag('news_open', new ResolvedParameters(['2', 'blank']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="http://domain.tld/news/foo-is-not-bar.html" target="_blank" rel="noreferrer noopener">',
            $listener(new ResolvedInsertTag('news_open', new ResolvedParameters(['2', 'absolute', 'blank']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="http://domain.tld/news/foo-is-not-bar.html" target="_blank" rel="noreferrer noopener">',
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
