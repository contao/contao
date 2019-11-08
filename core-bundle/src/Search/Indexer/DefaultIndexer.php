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

    public function __construct(ContaoFramework $framework, Connection $connection, bool $indexProtected = false)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->indexProtected = $indexProtected;
    }

    /**
     * {@inheritdoc}
     */
    public function index(Document $document): void
    {
        if (200 !== $document->getStatusCode()) {
            return;
        }

        $meta = [
            'title' => 'undefined',
            'language' => 'en',
            'protected' => false,
            'groups' => [],
            'pageId' => 0,
            'noSearch' => true, // Causes the indexer to skip this document if there is no json-ld data
        ];

        $this->extendMetaFromJsonLdScripts($document, $meta);

        // If search was disabled in the page settings, we do not index
        if (isset($meta['noSearch']) && true === $meta['noSearch']) {
            return;
        }

        // If the front end preview is activated, we do not index
        if (isset($meta['fePreview']) && true === $meta['fePreview']) {
            return;
        }

        // If the page is protected and no member is logged in or indexing protecting pages is disabled, we do not index
        if (isset($meta['protected']) && true === $meta['protected'] && !$this->indexProtected) {
            return;
        }

        $this->framework->initialize();

        /** @var Search $search */
        $search = $this->framework->getAdapter(Search::class);

        $search->indexPage([
            'url' => (string) $document->getUri(),
            'content' => $document->getBody(),
            'protected' => $meta['protected'] ? '1' : '',
            'groups' => $meta['groups'],
            'pid' => $meta['pageId'],
            'title' => $meta['title'],
            'language' => $meta['language'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Document $document): void
    {
        $this->framework->initialize();

        /** @var Search $search */
        $search = $this->framework->getAdapter(Search::class);
        $search->removeEntry((string) $document->getUri());
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->connection->exec('TRUNCATE TABLE tl_search');
        $this->connection->exec('TRUNCATE TABLE tl_search_index');
    }

    private function extendMetaFromJsonLdScripts(Document $document, array &$meta): void
    {
        $jsonLds = $document->extractJsonLdScripts('https://contao.org/', 'PageMetaData');

        if (0 === \count($jsonLds)) {
            return;
        }

        // Merge all entries to one meta array (the latter overrides the former)
        $meta = array_merge($meta, array_merge(...$jsonLds));
    }
}
