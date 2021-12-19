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

use Contao\CoreBundle\Search\Document;

class DelegatingIndexer implements IndexerInterface
{
    /**
     * @var array<IndexerInterface>
     */
    private array $indexers = [];

    public function addIndexer(IndexerInterface $indexer): self
    {
        $this->indexers[] = $indexer;

        return $this;
    }

    public function index(Document $document): void
    {
        foreach ($this->indexers as $indexer) {
            $indexer->index($document);
        }
    }

    public function delete(Document $document): void
    {
        foreach ($this->indexers as $indexer) {
            $indexer->delete($document);
        }
    }

    public function clear(): void
    {
        foreach ($this->indexers as $indexer) {
            $indexer->clear();
        }
    }
}
