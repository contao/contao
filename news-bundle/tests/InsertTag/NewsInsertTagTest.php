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
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\NewsBundle\InsertTag\NewsInsertTag;
use Contao\NewsModel;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NewsInsertTagTest extends ContaoTestCase
{
    #[DataProvider('replacesNewsTagsProvider')]
    public function testReplacesTheNewsTags(string $insertTag, array $parameters, int|null $referenceType, string|null $url, string $expectedValue, OutputType $expectedOutputType): void
    {
        $newsModel = $this->mockClassWithProperties(NewsModel::class);
        $newsModel->headline = '"Foo" is not "bar"';
        $newsModel->teaser = '<p>Foo does not equal bar.</p>';

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $newsModel]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects(null === $url ? $this->never() : $this->once())
            ->method('generate')
            ->with($newsModel, [], $referenceType)
            ->willReturn($url ?? '')
        ;

        $listener = new NewsInsertTag($this->mockContaoFramework($adapters), $urlGenerator);
        $result = $listener(new ResolvedInsertTag($insertTag, new ResolvedParameters($parameters), []));

        $this->assertSame($expectedValue, $result->getValue());
        $this->assertSame($expectedOutputType, $result->getOutputType());
    }

    public static function replacesNewsTagsProvider(): iterable
    {
        yield [
            'news',
            ['2'],
            UrlGeneratorInterface::ABSOLUTE_PATH,
            'news/foo-is-not-bar.html',
            '<a href="news/foo-is-not-bar.html">"Foo" is not "bar"</a>',
            OutputType::html,
        ];

        yield [
            'news',
            ['2', 'blank'],
            UrlGeneratorInterface::ABSOLUTE_PATH,
            'news/foo-is-not-bar.html',
            '<a href="news/foo-is-not-bar.html" target="_blank" rel="noreferrer noopener">"Foo" is not "bar"</a>',
            OutputType::html,
        ];

        yield [
            'news_open',
            ['2'],
            UrlGeneratorInterface::ABSOLUTE_PATH,
            'news/foo-is-not-bar.html',
            '<a href="news/foo-is-not-bar.html">',
            OutputType::html,
        ];

        yield [
            'news_open',
            ['2', 'blank'],
            UrlGeneratorInterface::ABSOLUTE_PATH,
            'news/foo-is-not-bar.html',
            '<a href="news/foo-is-not-bar.html" target="_blank" rel="noreferrer noopener">',
            OutputType::html,
        ];

        yield [
            'news_open',
            ['2', 'absolute', 'blank'],
            UrlGeneratorInterface::ABSOLUTE_URL,
            'http://domain.tld/news/foo-is-not-bar.html',
            '<a href="http://domain.tld/news/foo-is-not-bar.html" target="_blank" rel="noreferrer noopener">',
            OutputType::html,
        ];

        yield [
            'news_open',
            ['2', 'blank', 'absolute'],
            UrlGeneratorInterface::ABSOLUTE_URL,
            'http://domain.tld/news/foo-is-not-bar.html',
            '<a href="http://domain.tld/news/foo-is-not-bar.html" target="_blank" rel="noreferrer noopener">',
            OutputType::html,
        ];

        yield [
            'news_url',
            ['2'],
            UrlGeneratorInterface::ABSOLUTE_PATH,
            'news/foo-is-not-bar.html',
            'news/foo-is-not-bar.html',
            OutputType::url,
        ];

        yield [
            'news_url',
            ['2', 'absolute'],
            UrlGeneratorInterface::ABSOLUTE_URL,
            'http://domain.tld/news/foo-is-not-bar.html',
            'http://domain.tld/news/foo-is-not-bar.html',
            OutputType::url,
        ];

        yield [
            'news_url',
            ['2', 'absolute', 'blank'],
            UrlGeneratorInterface::ABSOLUTE_URL,
            'http://domain.tld/news/foo-is-not-bar.html',
            'http://domain.tld/news/foo-is-not-bar.html',
            OutputType::url,
        ];

        yield [
            'news_url',
            ['2', 'blank', 'absolute'],
            UrlGeneratorInterface::ABSOLUTE_URL,
            'http://domain.tld/news/foo-is-not-bar.html',
            'http://domain.tld/news/foo-is-not-bar.html',
            OutputType::url,
        ];

        yield [
            'news_title',
            ['2'],
            null,
            null,
            '"Foo" is not "bar"',
            OutputType::text,
        ];

        yield [
            'news_teaser',
            ['2'],
            null,
            null,
            '<p>Foo does not equal bar.</p>',
            OutputType::html,
        ];
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
