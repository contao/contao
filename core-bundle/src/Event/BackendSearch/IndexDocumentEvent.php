<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event\BackendSearch;

use Contao\CoreBundle\Search\Backend\Document;

/**
 * @experimental
 */
final class IndexDocumentEvent
{
    public function __construct(private Document|null $document)
    {
    }

    public function setDocument(Document|null $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function getDocument(): Document|null
    {
        return $this->document;
    }
}
