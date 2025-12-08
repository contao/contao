<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\InsertTag;

use Contao\CalendarBundle\InsertTag\EventInsertTag;
use Contao\CalendarEventsModel;
use Contao\CalendarFeedModel;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EventInsertTagTest extends ContaoTestCase
{
    public function testReplacesTheCalendarFeedTag(): void
    {
        $feedModel = $this->mockClassWithProperties(CalendarFeedModel::class);
        $feedModel->feedBase = 'http://localhost/';
        $feedModel->alias = 'events';

        $adapters = [
            CalendarFeedModel::class => $this->mockConfiguredAdapter(['findById' => $feedModel]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $urlGenerator = $this->createStub(ContentUrlGenerator::class);

        $listener = new EventInsertTag($framework, $urlGenerator);
        $url = $listener(new ResolvedInsertTag('calendar_feed', new ResolvedParameters(['2']), []));

        $this->assertEquals(new InsertTagResult('http://localhost/share/events.xml', OutputType::url), $url);
    }

    #[DataProvider('replacesTheEventTagsProvider')]
    public function testReplacesTheEventTags(string $insertTag, array $parameters, int|null $referenceType, string|null $url, string $expectedValue, OutputType $expectedOutputType): void
    {
        $eventModel = $this->mockClassWithProperties(CalendarEventsModel::class);
        $eventModel->title = 'The "foobar" event';
        $eventModel->teaser = '<p>The annual foobar event.</p>';

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $eventModel]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects(null === $url ? $this->never() : $this->once())
            ->method('generate')
            ->with($eventModel, [], $referenceType)
            ->willReturn($url ?? '')
        ;

        $listener = new EventInsertTag($this->mockContaoFramework($adapters), $urlGenerator);
        $result = $listener(new ResolvedInsertTag($insertTag, new ResolvedParameters($parameters), []));

        $this->assertSame($expectedValue, $result->getValue());
        $this->assertSame($expectedOutputType, $result->getOutputType());
    }

    public static function replacesTheEventTagsProvider(): iterable
    {
        yield [
            'event',
            ['2'],
            UrlGeneratorInterface::ABSOLUTE_PATH,
            'events/the-foobar-event.html',
            '<a href="events/the-foobar-event.html">The "foobar" event</a>',
            OutputType::html,
        ];

        yield [
            'event',
            ['2', 'blank'],
            UrlGeneratorInterface::ABSOLUTE_PATH,
            'events/the-foobar-event.html',
            '<a href="events/the-foobar-event.html" target="_blank" rel="noreferrer noopener">The "foobar" event</a>',
            OutputType::html,
        ];

        yield [
            'event_open',
            ['2'],
            UrlGeneratorInterface::ABSOLUTE_PATH,
            'events/the-foobar-event.html',
            '<a href="events/the-foobar-event.html">',
            OutputType::html,
        ];

        yield [
            'event_open',
            ['2', 'blank'],
            UrlGeneratorInterface::ABSOLUTE_PATH,
            'events/the-foobar-event.html',
            '<a href="events/the-foobar-event.html" target="_blank" rel="noreferrer noopener">',
            OutputType::html,
        ];

        yield [
            'event_open',
            ['2', 'blank', 'absolute'],
            UrlGeneratorInterface::ABSOLUTE_URL,
            'http://domain.tld/events/the-foobar-event.html',
            '<a href="http://domain.tld/events/the-foobar-event.html" target="_blank" rel="noreferrer noopener">',
            OutputType::html,
        ];

        yield [
            'event_open',
            ['2', 'absolute', 'blank'],
            UrlGeneratorInterface::ABSOLUTE_URL,
            'http://domain.tld/events/the-foobar-event.html',
            '<a href="http://domain.tld/events/the-foobar-event.html" target="_blank" rel="noreferrer noopener">',
            OutputType::html,
        ];

        yield [
            'event_url',
            ['2'],
            UrlGeneratorInterface::ABSOLUTE_PATH,
            'events/the-foobar-event.html',
            'events/the-foobar-event.html',
            OutputType::url,
        ];

        yield [
            'event_url',
            ['2', 'absolute'],
            UrlGeneratorInterface::ABSOLUTE_URL,
            'http://domain.tld/events/the-foobar-event.html',
            'http://domain.tld/events/the-foobar-event.html',
            OutputType::url,
        ];

        yield [
            'event_url',
            ['2', 'absolute', 'blank'],
            UrlGeneratorInterface::ABSOLUTE_URL,
            'http://domain.tld/events/the-foobar-event.html',
            'http://domain.tld/events/the-foobar-event.html',
            OutputType::url,
        ];

        yield [
            'event_url',
            ['2', 'blank', 'absolute'],
            UrlGeneratorInterface::ABSOLUTE_URL,
            'http://domain.tld/events/the-foobar-event.html',
            'http://domain.tld/events/the-foobar-event.html',
            OutputType::url,
        ];

        yield [
            'event_title',
            ['2'],
            null,
            null,
            'The "foobar" event',
            OutputType::text,
        ];

        yield [
            'event_teaser',
            ['2'],
            null,
            null,
            '<p>The annual foobar event.</p>',
            OutputType::html,
        ];
    }

    public function testReturnsAnEmptyStringIfThereIsNoModel(): void
    {
        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => null]),
            CalendarFeedModel::class => $this->mockConfiguredAdapter(['findById' => null]),
        ];

        $urlGenerator = $this->createStub(ContentUrlGenerator::class);
        $listener = new EventInsertTag($this->mockContaoFramework($adapters), $urlGenerator);

        $this->assertSame('', $listener(new ResolvedInsertTag('calendar_feed', new ResolvedParameters(['3']), []))->getValue());
        $this->assertSame('', $listener(new ResolvedInsertTag('event_url', new ResolvedParameters(['3']), []))->getValue());
    }
}
