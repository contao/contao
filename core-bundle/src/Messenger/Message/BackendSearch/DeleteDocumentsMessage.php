<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger\Message\BackendSearch;

use Contao\CoreBundle\Messenger\Message\LowPriorityMessageInterface;

/**
 * @experimental
 */
class DeleteDocumentsMessage implements LowPriorityMessageInterface
{
    /**
     * @param array<string, array<string>> $documentTypesAndIds The document IDs grouped by type
     */
    public function __construct(private readonly array $documentTypesAndIds)
    {
    }

    /**
     * @return array<string, array<string>>
     */
    public function getDocumentTypesAndIds(): array
    {
        return $this->documentTypesAndIds;
    }
}
