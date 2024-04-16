<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Crawl\Monolog;

use Contao\CoreBundle\Crawl\Monolog\CrawlCsvLogHandler;
use Monolog\Logger;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Terminal42\Escargot\CrawlUri;

class CrawlCsvLogHandlerTest extends TestCase
{
    /**
     * @dataProvider writesCsvStreamProvider
     */
    public function testWritesCsvStream(\DateTimeImmutable $dt, array $context, string $expectedContent, string $existingCsvContent = '', string $message = 'foobar'): void
    {
        $stream = fopen('php://memory', 'r+');

        if ($existingCsvContent) {
            fwrite($stream, $existingCsvContent);
        }

        $handler = new CrawlCsvLogHandler($stream);
        $handler->handle(['level' => Logger::DEBUG, 'level_name' => 'DEBUG', 'channel' => 'test', 'message' => $message, 'extra' => [], 'context' => $context, 'datetime' => $dt]);

        rewind($stream);
        $content = stream_get_contents($stream);

        $this->assertSame($expectedContent, $content);
    }

    public function testSourceFilter(): void
    {
        $dt = new \DateTimeImmutable();
        $formattedDt = '"'.$dt->format(CrawlCsvLogHandler::DATETIME_FORMAT).'"';

        $record = [
            'level' => Logger::DEBUG,
            'level_name' => 'DEBUG',
            'message' => 'foobar',
            'channel' => 'test',
            'extra' => [],
            'datetime' => $dt,
            'context' => [
                'source' => 'source',
                'crawlUri' => new CrawlUri(new Uri('https://contao.org'), 0),
            ],
        ];

        $stream = fopen('php://memory', 'r+');
        $handler = new CrawlCsvLogHandler($stream);
        $handler->setFilterSource('foobar');
        $handler->handle($record);

        rewind($stream);
        $content = stream_get_contents($stream);
        $this->assertSame('', $content);

        $handler = new CrawlCsvLogHandler($stream);
        $handler->setFilterSource('source');
        $handler->handle($record);

        rewind($stream);
        $content = stream_get_contents($stream);

        $this->assertSame('Time,Source,URI,"Found on URI","Found on level",Tags,Message'."\n".$formattedDt.',source,https://contao.org/,,0,,foobar'."\n", $content);
    }

    public static function writesCsvStreamProvider(): iterable
    {
        $dt = new \DateTimeImmutable();
        $formattedDt = '"'.$dt->format(CrawlCsvLogHandler::DATETIME_FORMAT).'"';

        yield 'Should not write anything if the source is missing' => [
            $dt,
            [],
            '',
        ];

        yield 'Correctly logs with no CrawlUri provided and empty log file (should write headlines)' => [
            $dt,
            ['source' => 'source'],
            'Time,Source,URI,"Found on URI","Found on level",Tags,Message'."\n".$formattedDt.',source,---,---,---,---,foobar'."\n",
        ];

        yield 'Correctly logs with CrawlUri provided and empty log file (should write headlines)' => [
            $dt,
            ['source' => 'source', 'crawlUri' => new CrawlUri(new Uri('https://contao.org'), 0)],
            'Time,Source,URI,"Found on URI","Found on level",Tags,Message'."\n".$formattedDt.',source,https://contao.org/,,0,,foobar'."\n",
        ];

        yield 'Correctly logs with no CrawlUri provided and a non-empty log file (should not write headlines)' => [
            $dt,
            ['source' => 'source'],
            'Time,Source,URI,"Found on URI","Found on level",Tags,Message'."\n".$formattedDt.',source,---,---,---,---,foobar'."\n",
            'Time,Source,URI,"Found on URI","Found on level",Tags,Message'."\n",
        ];

        yield 'Correctly logs with CrawlUri provided and a non-empty log file (should not write headlines)' => [
            $dt,
            ['source' => 'source', 'crawlUri' => new CrawlUri(new Uri('https://contao.org'), 0)],
            'Time,Source,URI,"Found on URI","Found on level",Tags,Message'."\n".$formattedDt.',source,https://contao.org/,,0,,foobar'."\n",
            'Time,Source,URI,"Found on URI","Found on level",Tags,Message'."\n",
        ];

        yield 'Correctly logs with new lines in message' => [
            $dt,
            ['source' => 'source', 'crawlUri' => new CrawlUri(new Uri('https://contao.org'), 0)],
            'Time,Source,URI,"Found on URI","Found on level",Tags,Message'."\n".$formattedDt.',source,https://contao.org/,,0,,"foobar with new lines"'."\n",
            'Time,Source,URI,"Found on URI","Found on level",Tags,Message'."\n",
            "foobar\rwith\nnew\r\nlines",
        ];
    }
}
