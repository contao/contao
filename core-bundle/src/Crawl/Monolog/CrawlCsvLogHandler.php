<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Crawl\Monolog;

use Monolog\Handler\StreamHandler;
use Terminal42\Escargot\CrawlUri;

class CrawlCsvLogHandler extends StreamHandler
{
    /**
     * @var string
     */
    private $filterSource;

    public function getFilterSource(): string
    {
        return $this->filterSource;
    }

    public function setFilterSource(string $filterSource): self
    {
        $this->filterSource = $filterSource;

        return $this;
    }

    protected function streamWrite($resource, array $record): void
    {
        if (!isset($record['context']['source'])) {
            return;
        }

        if ($this->filterSource && $this->filterSource !== $record['context']['source']) {
            return;
        }

        /** @var CrawlUri $crawlUri */
        $crawlUri = $record['context']['crawlUri'] ?? null;

        $stat = fstat($resource);
        $size = $stat['size'];

        if (0 === $size) {
            fputcsv($resource, [
                'Source',
                'URI',
                'Found on URI',
                'Found on level',
                'Tags',
                'Message',
            ]);
        }

        $columns = [
            $record['context']['source'],
            null === $crawlUri ? '---' : (string) $crawlUri->getUri(),
            null === $crawlUri ? '---' : (string) $crawlUri->getFoundOn(),
            null === $crawlUri ? '---' : $crawlUri->getLevel(),
            null === $crawlUri ? '---' : implode(', ', $crawlUri->getTags()),
            preg_replace('/\r\n|\n|\r/', ' ', $record['message']),
        ];

        fputcsv($resource, $columns);
    }
}
