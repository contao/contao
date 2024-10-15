<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Event\BackendSearch;

use Contao\CoreBundle\Search\Backend\Document;

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
