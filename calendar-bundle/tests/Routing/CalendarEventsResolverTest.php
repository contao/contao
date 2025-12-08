<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\Routing;

use Contao\ArticleModel;
use Contao\CalendarBundle\Routing\CalendarEventsResolver;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Routing\Content\StringUrl;
use Contao\Model;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CalendarEventsResolverTest extends ContaoTestCase
{
    public function testResolveEventWithExternalSource(): void
    {
        $content = $this->mockClassWithProperties(CalendarEventsModel::class, ['source' => 'external', 'url' => 'foobar']);

        $resolver = new CalendarEventsResolver($this->createContaoFrameworkStub());
        $result = $resolver->resolve($content);

        $this->assertTrue($result->isRedirect());
        $this->assertInstanceOf(StringUrl::class, $result->content);
        $this->assertSame('foobar', $result->content->value);
    }

    public function testResolveEventWithInternalSource(): void
    {
        $jumpTo = $this->createStub(PageModel::class);
        $content = $this->mockClassWithProperties(CalendarEventsModel::class, ['source' => 'internal', 'jumpTo' => 42]);

        $pageAdapter = $this->createAdapterMock(['findById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($jumpTo)
        ;

        $framework = $this->createContaoFrameworkStub([PageModel::class => $pageAdapter]);

        $resolver = new CalendarEventsResolver($framework);
        $result = $resolver->resolve($content);

        $this->assertTrue($result->isRedirect());
        $this->assertSame($jumpTo, $result->content);
    }

    public function testResolveEventWithArticleSource(): void
    {
        $article = $this->createStub(ArticleModel::class);
        $content = $this->mockClassWithProperties(CalendarEventsModel::class, ['source' => 'article', 'articleId' => 42]);

        $articleAdapter = $this->createAdapterMock(['findById']);
        $articleAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($article)
        ;

        $framework = $this->createContaoFrameworkStub([ArticleModel::class => $articleAdapter]);

        $resolver = new CalendarEventsResolver($framework);
        $result = $resolver->resolve($content);

        $this->assertTrue($result->isRedirect());
        $this->assertSame($article, $result->content);
    }

    public function testResolveEventWithoutSource(): void
    {
        $target = $this->createStub(PageModel::class);
        $calendar = $this->mockClassWithProperties(CalendarModel::class, ['jumpTo' => 42]);
        $content = $this->mockClassWithProperties(CalendarEventsModel::class, ['source' => '']);

        $pageAdapter = $this->createAdapterMock(['findById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($target)
        ;

        $framework = $this->createContaoFrameworkStub([
            PageModel::class => $pageAdapter,
            CalendarModel::class => $this->mockConfiguredAdapter(['findById' => $calendar]),
        ]);

        $resolver = new CalendarEventsResolver($framework);
        $result = $resolver->resolve($content);

        $this->assertFalse($result->isRedirect());
        $this->assertSame($target, $result->content);
    }

    /**
     * @param class-string<Model> $class
     */
    #[DataProvider('getParametersForContentProvider')]
    public function testGetParametersForContent(string $class, array $properties, array $expected): void
    {
        $content = $this->mockClassWithProperties($class, $properties);

        $pageModel = $this->createStub(PageModel::class);
        $resolver = new CalendarEventsResolver($this->createContaoFrameworkStub());

        $this->assertSame($expected, $resolver->getParametersForContent($content, $pageModel));
    }

    public static function getParametersForContentProvider(): iterable
    {
        yield 'Uses the event alias' => [
            CalendarEventsModel::class,
            ['id' => 42, 'alias' => 'foobar'],
            ['parameters' => '/foobar'],
        ];

        yield 'Uses event ID if alias is empty' => [
            CalendarEventsModel::class,
            ['id' => 42, 'alias' => ''],
            ['parameters' => '/42'],
        ];

        yield 'Only supports CalendarEventsModel' => [
            PageModel::class,
            [],
            [],
        ];
    }
}
