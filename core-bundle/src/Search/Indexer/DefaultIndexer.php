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
use Doctrine\DBAL\Driver\Connection;

class DefaultIndexer implements IndexerInterface
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var bool
     */
    private $indexProtected;

    /**
     * @internal Do not inherit from this class; decorate the "contao.search.indexer.default" service instead
     */
    public function __construct(ContaoFramework $framework, Connection $connection, bool $indexProtected = false)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->indexProtected = $indexProtected;
    }

    public function index(Document $document): void
    {
        if (200 !== $document->getStatusCode()) {
            $this->throwBecause('HTTP Statuscode is not equal to 200.');
        }

        if ('' === $document->getBody()) {
            $this->throwBecause('Cannot index empty response.');
        }

        try {
            $title = $document->getContentCrawler()->filterXPath('//head/title')->first()->text(null, true);
        } catch (\Exception $e) {
            $title = 'undefined';
        }

        try {
            $language = $document->getContentCrawler()->filterXPath('//html[@lang]')->first()->attr('lang');
        } catch (\Exception $e) {
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

        /** @var Search $search */
        $search = $this->framework->getAdapter(Search::class);

        try {
            $search->indexPage([
                'url' => (string) $document->getUri(),
                'content' => $document->getBody(),
                'protected' => $meta['protected'] ? '1' : '',
                'groups' => $meta['groups'],
                'pid' => $meta['pageId'],
                'title' => $meta['title'],
                'language' => $meta['language'],
            ]);
        } catch (\Throwable $t) {
            $this->throwBecause('Could not add a search index entry: '.$t->getMessage(), false);
        }
    }

    public function delete(Document $document): void
    {
        // Do not initialize framework, (see #5426)

        /** @var Search $search */
        $search = $this->framework->getAdapter(Search::class);
        $search->removeEntry((string) $document->getUri(), $this->connection);
    }

    public function clear(): void
    {
        $this->connection->exec('TRUNCATE TABLE tl_search');
        $this->connection->exec('TRUNCATE TABLE tl_search_index');
    }

    /**
     * @throws IndexerException
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
            $jsonLds = $document->extractJsonLdScripts('https://schema.contao.org/', 'RegularPage');

            if (0 === \count($jsonLds)) {
                $this->throwBecause('No JSON-LD found.');
            }

            @trigger_error('Using the JSON-LD type "RegularPage" has been deprecated and will no longer work in Contao 5.0. Use "Page" instead.', E_USER_DEPRECATED);
        }

        // Merge all entries to one meta array (the latter overrides the former)
        $meta = array_merge($meta, array_merge(...$jsonLds));
    }
}
