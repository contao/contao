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
        $this->wrapInTryCatch(
            static function (IndexerInterface $indexer) use ($document): void {
                $indexer->index($document);
            },
        );
    }

    public function delete(Document $document): void
    {
        $this->wrapInTryCatch(
            static function (IndexerInterface $indexer) use ($document): void {
                $indexer->delete($document);
            },
        );
    }

    public function clear(): void
    {
        $this->wrapInTryCatch(
            static function (IndexerInterface $indexer): void {
                $indexer->clear();
            },
        );
    }

    private function wrapInTryCatch(callable $function): void
    {
        $warningsOnly = true;
        $indexerExceptions = [];

        foreach ($this->indexers as $indexer) {
            try {
                $function($indexer);
            } catch (IndexerException $exception) {
                $indexerExceptions[] = $exception;

                if (!$exception->isOnlyWarning()) {
                    $warningsOnly = false;
                }
            }
        }

        if ([] !== $indexerExceptions) {
            if ($warningsOnly) {
                throw IndexerException::createAsWarning($this->getMergedExceptionMessage($indexerExceptions), 0, $indexerExceptions[0]);
            }

            throw new IndexerException($this->getMergedExceptionMessage($indexerExceptions), 0, $indexerExceptions[0]);
        }
    }

    /**
     * @param array<\Throwable> $exceptions
     */
    private function getMergedExceptionMessage(array $exceptions): string
    {
        $messages = [];

        foreach ($exceptions as $exception) {
            $messages[] = $exception->getMessage();
        }

        return implode(' | ', $messages);
    }
}
