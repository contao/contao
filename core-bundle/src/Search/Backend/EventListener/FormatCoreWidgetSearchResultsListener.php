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
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class FormatCoreWidgetSearchResultsListener
{
    public function __invoke(FormatTableDataContainerDocumentEvent $event): void
    {
        $event->setSearchableContent(match ($event->getFieldConfig()['inputType'] ?? null) {
            'textarea' => $this->handleTextarea($event),
            'inputUnit' => $this->handleInputUnit($event),
            default => $event->getSearchableContent(), // leave untouched
        });
    }

    private function handleTextarea(FormatTableDataContainerDocumentEvent $event): string
    {
        // Strip HTML
        return strip_tags($event->getSearchableContent());
    }

    private function handleInputUnit(FormatTableDataContainerDocumentEvent $event): string
    {
        $chunks = StringUtil::deserialize($event->getSearchableContent());

        // Not in the format we expect, maybe changed by an earlier listener already?
        if (!isset($chunks['unit']) && !isset($chunks['value'])) {
            return $event->getSearchableContent();
        }

        return $chunks['value'];
    }
}
