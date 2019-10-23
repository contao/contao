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
     */
    public function index(Document $document): void;

    /**
     * Clears the search index.
     */
    public function clear(): void;
}
