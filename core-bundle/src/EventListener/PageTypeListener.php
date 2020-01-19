<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\PageType\HasLegacyPageInterface;
use Contao\CoreBundle\PageType\LegacyPageType;
use Contao\CoreBundle\PageType\PageTypeRegistry;

class PageTypeListener
{
    /**
     * @var PageTypeRegistry
     */
    private $pageTypeRegistry;

    public function __construct(PageTypeRegistry $pageTypeRegistry)
    {
        $this->pageTypeRegistry = $pageTypeRegistry;
    }

    public function onInitializeSystem(): void
    {
        $legacyPageTypes = $GLOBALS['TL_PTY'] ?? [];

        $this->registerLegacyPageClasses();
        $this->registerLegacyPageTypes($legacyPageTypes);
    }

    public function registerLegacyPageClasses(): void
    {
        foreach ($this->pageTypeRegistry as $pageType) {
            if ($pageType instanceof HasLegacyPageInterface) {
                $GLOBALS['TL_PTY'][$pageType->getName()] = $pageType->getLegacyPageClass();
            }
        }
    }

    /**
     * Register a list of legacy page types.
     *
     * A legacy page type is a page class which is only registered at $GLOBALS['TL_PTY']
     *
     * @param string[] $legacyPageTypes
     */
    public function registerLegacyPageTypes(array $legacyPageTypes): void
    {
        foreach ($legacyPageTypes ?? [] as $pageType) {
            $this->pageTypeRegistry->register(new LegacyPageType($pageType));
        }
    }
}
