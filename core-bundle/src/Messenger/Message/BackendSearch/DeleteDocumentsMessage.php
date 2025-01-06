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
use Contao\CoreBundle\Messenger\Message\ScopeAwareMessageInterface;
use Contao\CoreBundle\Messenger\Message\ScopeAwareMessageTrait;
use Contao\CoreBundle\Search\Backend\GroupedDocumentIds;

/**
 * @experimental
 */
class DeleteDocumentsMessage implements LowPriorityMessageInterface, ScopeAwareMessageInterface
{
    use ScopeAwareMessageTrait;

    /**
     * @var array<string, array<string>>
     */
    private readonly array $asArray;

    public function __construct(GroupedDocumentIds $groupedDocumentIds)
    {
        $this->asArray = $groupedDocumentIds->toArray();
    }

    public function getGroupedDocumentIds(): GroupedDocumentIds
    {
        return GroupedDocumentIds::fromArray($this->asArray);
    }
}
