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
     * @param array<string> $documentsIds
     */
    public function __construct(private readonly array $documentsIds)
    {
    }

    /**
     * @return array<string>
     */
    public function getDocumentIds(): array
    {
        return $this->documentsIds;
    }
}
