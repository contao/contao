<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\Backend\EventListener;

use Contao\CoreBundle\Search\Backend\Event\FormatTableDataContainerDocumentEvent;
use Contao\StringUtil;
use Contao\Validator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class FormatBinaryUuidSearchResultsListener
{
    public function __invoke(FormatTableDataContainerDocumentEvent $event): void
    {
        $event->setSearchableContent($this->convertBinaryUuidToHex($event->getSearchableContent()));
    }

    private function convertBinaryUuidToHex(string $uuid): string
    {
        if (Validator::isBinaryUuid($uuid)) {
            return StringUtil::binToUuid($uuid);
        }

        return $uuid;
    }
}
