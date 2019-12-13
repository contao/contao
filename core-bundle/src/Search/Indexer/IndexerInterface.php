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

interface IndexerInterface
{
    /**
     * Indexes a given document.
     *
     * @throws IndexerException If indexing did not work
     */
    public function index(Document $document): void;

    /**
     * Deletes a given document.
     *
     * @throws IndexerException If deleting did not work
     */
    public function delete(Document $document): void;

    /**
     * Clears the search index.
     *
     * @throws IndexerException If clearing did not work
     */
    public function clear(): void;
}
