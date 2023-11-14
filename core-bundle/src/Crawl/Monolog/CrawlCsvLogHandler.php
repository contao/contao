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
    final public const DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    private string|null $filterSource = null;

    public function getFilterSource(): string
    {
        return $this->filterSource;
    }

    public function setFilterSource(string $filterSource): self
    {
        $this->filterSource = $filterSource;

        return $this;
    }

    #[\Override]
    protected function streamWrite($stream, array $record): void
    {
        if (!isset($record['context']['source'])) {
            return;
        }

        if ($this->filterSource && $this->filterSource !== $record['context']['source']) {
            return;
        }

        $crawlUri = null;

        if (($record['context']['crawlUri'] ?? null) instanceof CrawlUri) {
            $crawlUri = $record['context']['crawlUri'];
        }

        $stat = fstat($stream);
        $size = $stat['size'];

        if (0 === $size) {
            fputcsv($stream, ['Time', 'Source', 'URI', 'Found on URI', 'Found on level', 'Tags', 'Message']);
        }

        $columns = [
            $record['datetime']->format(self::DATETIME_FORMAT),
            $record['context']['source'],
            !$crawlUri ? '---' : (string) $crawlUri->getUri(),
            !$crawlUri ? '---' : (string) $crawlUri->getFoundOn(),
            !$crawlUri ? '---' : $crawlUri->getLevel(),
            !$crawlUri ? '---' : implode(', ', $crawlUri->getTags()),
            preg_replace('/\r\n|\n|\r/', ' ', $record['message']),
        ];

        fputcsv($stream, $columns);
    }
}
