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
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Monolog\Logger;
use Terminal42\Escargot\CrawlUri;

class CrawlCsvLogHandlerTest extends TestCase
{
    /**
     * @dataProvider writesCsvStreamProvider
     */
    public function testWritesCsvStream(array $context, string $expectedContent, string $existingCsvContent = '', $message = 'foobar'): void
    {
        $stream = fopen('php://memory', 'r+');

        if ($existingCsvContent) {
            fwrite($stream, $existingCsvContent);
        }

        $handler = new CrawlCsvLogHandler($stream);
        $handler->handle(['level' => Logger::DEBUG, 'message' => $message, 'extra' => [], 'context' => $context]);

        rewind($stream);
        $content = stream_get_contents($stream);

        $this->assertSame($expectedContent, $content);
    }

    public function testSourceFilter(): void
    {
        $record = [
            'level' => Logger::DEBUG,
            'message' => 'foobar',
            'extra' => [],
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

        $this->assertSame('Source,URI,"Found on URI","Found on level",Tags,Message'."\n".'source,https://contao.org/,,0,,foobar'."\n", $content);
    }

    public function writesCsvStreamProvider(): \Generator
    {
        yield 'Should not write anything if the source is missing' => [
            [],
            '',
        ];

        yield 'Correctly logs with no CrawlUri provided and empty log file (should write headlines)' => [
            ['source' => 'source'],
            'Source,URI,"Found on URI","Found on level",Tags,Message'."\n".'source,---,---,---,---,foobar'."\n",
        ];

        yield 'Correctly logs with CrawlUri provided and empty log file (should write headlines)' => [
            ['source' => 'source', 'crawlUri' => new CrawlUri(new Uri('https://contao.org'), 0)],
            'Source,URI,"Found on URI","Found on level",Tags,Message'."\n".'source,https://contao.org/,,0,,foobar'."\n",
        ];

        yield 'Correctly logs with no CrawlUri provided and a non-empty log file (should not write headlines)' => [
            ['source' => 'source'],
            'Source,URI,"Found on URI","Found on level",Tags,Message'."\n".'source,---,---,---,---,foobar'."\n",
            'Source,URI,"Found on URI","Found on level",Tags,Message'."\n",
        ];

        yield 'Correctly logs with CrawlUri provided and a non-empty log file (should not write headlines)' => [
            ['source' => 'source', 'crawlUri' => new CrawlUri(new Uri('https://contao.org'), 0)],
            'Source,URI,"Found on URI","Found on level",Tags,Message'."\n".'source,https://contao.org/,,0,,foobar'."\n",
            'Source,URI,"Found on URI","Found on level",Tags,Message'."\n",
        ];

        yield 'Correctly logs with new lines in message' => [
            ['source' => 'source', 'crawlUri' => new CrawlUri(new Uri('https://contao.org'), 0)],
            'Source,URI,"Found on URI","Found on level",Tags,Message'."\n".'source,https://contao.org/,,0,,"foobar with new lines"'."\n",
            'Source,URI,"Found on URI","Found on level",Tags,Message'."\n",
            "foobar\rwith\nnew\r\nlines",
        ];
    }
}
