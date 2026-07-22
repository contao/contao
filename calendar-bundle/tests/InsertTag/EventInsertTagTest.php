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
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Inspector\Storage;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

class EventInsertTagTest extends ContaoTestCase
{
    #[DataProvider('replacesTheEventTagsProvider')]
    public function testReplacesTheEventTags(string $insertTag, array $parameters, int|null $referenceType, string|null $url, string $expectedValue, OutputType $expectedOutputType): void
    {
        $eventModel = $this->createClassWithPropertiesStub(CalendarEventsModel::class);
        $eventModel->title = 'The "foobar" event';
        $eventModel->teaser = '<p>The annual foobar event.</p>';

        $adapters = [
            CalendarEventsModel::class => $this->createConfiguredAdapterStub(['findByIdOrAlias' => $eventModel]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects(null === $url ? $this->never() : $this->once())
            ->method('generate')
            ->with($eventModel, [], $referenceType)
            ->willReturn($url ?? '')
        ;

        $listener = $this->createEventInsertTag($adapters, $urlGenerator);
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
            '<a href="events/the-foobar-event.html">The &quot;foobar&quot; event</a>',
            OutputType::html,
        ];

        yield [
            'event',
            ['2', 'blank'],
            UrlGeneratorInterface::ABSOLUTE_PATH,
            'events/the-foobar-event.html',
            '<a href="events/the-foobar-event.html" target="_blank" rel="noreferrer noopener">The &quot;foobar&quot; event</a>',
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
            CalendarEventsModel::class => $this->createConfiguredAdapterStub(['findByIdOrAlias' => null]),
        ];

        $urlGenerator = $this->createStub(ContentUrlGenerator::class);
        $listener = $this->createEventInsertTag($adapters, $urlGenerator);

        $this->assertSame('', $listener(new ResolvedInsertTag('event_url', new ResolvedParameters(['3']), []))->getValue());
    }

    private function createEventInsertTag(array $adapters, ContentUrlGenerator $urlGenerator): EventInsertTag
    {
        $twig = new Environment($this->createStub(LoaderInterface::class));

        $twig->addExtension(
            new ContaoExtension(
                $twig,
                $this->createStub(ContaoFilesystemLoader::class),
                $this->createStub(ContaoVariable::class),
                new InspectorNodeVisitor($this->createStub(Storage::class), $twig),
            ),
        );

        $insertTagParser = $this->createStub(InsertTagParser::class);
        $insertTagParser
            ->method('replace')
            ->willReturnArgument(0)
        ;

        $twig->addRuntimeLoader(
            new FactoryRuntimeLoader([
                InsertTagRuntime::class => static fn () => new InsertTagRuntime($insertTagParser),
            ]),
        );

        $htmlSanitizer = $this->createStub(HtmlSanitizerInterface::class);
        $htmlSanitizer
            ->method('sanitize')
            ->willReturnArgument(0)
        ;

        return new EventInsertTag($this->createContaoFrameworkStub($adapters), $urlGenerator, $twig, $htmlSanitizer);
    }
}
