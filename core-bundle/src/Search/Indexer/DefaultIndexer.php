<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\Indexer;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Search\Document;
use Contao\Search;
use Doctrine\DBAL\Connection;

class DefaultIndexer implements IndexerInterface
{
    /**
     * @internal
     */
    public function __construct(private ContaoFramework $framework, private Connection $connection, private bool $indexProtected = false)
    {
    }

    public function index(Document $document): void
    {
        if (200 !== $document->getStatusCode()) {
            $this->throwBecause('HTTP Statuscode is not equal to 200.');
        }

        if ('' === $document->getBody()) {
            $this->throwBecause('Cannot index empty response.');
        }

        if (($canonical = $document->extractCanonicalUri()) && ((string) $canonical !== (string) $document->getUri())) {
            $this->throwBecause(sprintf('Ignored because canonical URI "%s" does not match document URI.', $canonical));
        }

        try {
            $title = $document->getContentCrawler()->filterXPath('//head/title')->first()->text();
        } catch (\Exception) {
            $title = 'undefined';
        }

        try {
            $language = $document->getContentCrawler()->filterXPath('//html[@lang]')->first()->attr('lang');
        } catch (\Exception) {
            $language = 'en';
        }

        $meta = [
            'title' => $title,
            'language' => $language,
            'protected' => false,
            'groups' => [],
        ];

        $this->extendMetaFromJsonLdScripts($document, $meta);

        if (!isset($meta['pageId']) || 0 === $meta['pageId']) {
            $this->throwBecause('No page ID could be determined.');
        }

        // If search was disabled in the page settings, we do not index
        if (isset($meta['noSearch']) && true === $meta['noSearch']) {
            $this->throwBecause('Was explicitly marked "noSearch" in page settings.');
        }

        // If the front end preview is activated, we do not index
        if (isset($meta['fePreview']) && true === $meta['fePreview']) {
            $this->throwBecause('Indexing when the front end preview is enabled is not possible.');
        }

        // If the page is protected and indexing protecting pages is disabled, we do not index
        if (isset($meta['protected']) && true === $meta['protected'] && !$this->indexProtected) {
            $this->throwBecause('Indexing protected pages is disabled.');
        }

        $this->framework->initialize();

        $search = $this->framework->getAdapter(Search::class);

        try {
            $search->indexPage([
                'url' => (string) $document->getUri(),
                'content' => $document->getBody(),
                'protected' => (bool) $meta['protected'],
                'groups' => $meta['groups'],
                'pid' => $meta['pageId'],
                'title' => $meta['title'],
                'language' => $meta['language'],
                'meta' => $document->extractJsonLdScripts(),
            ]);
        } catch (\Throwable $t) {
            $this->throwBecause('Could not add a search index entry: '.$t->getMessage(), false);
        }
    }

    public function delete(Document $document): void
    {
        $search = $this->framework->getAdapter(Search::class);
        $search->removeEntry((string) $document->getUri(), $this->connection);
    }

    public function clear(): void
    {
        $this->connection->executeStatement('TRUNCATE TABLE tl_search');
        $this->connection->executeStatement('TRUNCATE TABLE tl_search_index');
        $this->connection->executeStatement('TRUNCATE TABLE tl_search_term');
    }

    /**
     * @return never
     */
    private function throwBecause(string $message, bool $onlyWarning = true): void
    {
        if ($onlyWarning) {
            throw IndexerException::createAsWarning($message);
        }

        throw new IndexerException($message);
    }

    private function extendMetaFromJsonLdScripts(Document $document, array &$meta): void
    {
        $jsonLds = $document->extractJsonLdScripts('https://schema.contao.org/', 'Page');

        if (0 === \count($jsonLds)) {
            $this->throwBecause('No JSON-LD found.');
        }

        // Merge all entries to one meta array (the latter overrides the former)
        $meta = array_merge($meta, array_merge(...$jsonLds));
    }
}
