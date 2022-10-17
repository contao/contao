<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Translation;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Symfony\Component\Translation\LocaleSwitcher as SymfonyLocaleSwitcher;

class LocaleSwitcher
{
    public function __construct(private SymfonyLocaleSwitcher $inner, private ContaoFramework $framework)
    {
    }

    public function runWithLocale(string $locale, callable $callback): mixed
    {
        $original = $this->inner->getLocale();

        try {
            return $this->inner->runWithLocale($locale, $callback);
        } finally {
            // Reload translations of previous language to $GLOBALS['TL_LANG']
            if ($original !== $locale && $callback instanceof TranslatorCallback && str_starts_with($callback->getDomain() ?? '', 'contao_')) {
                $system = $this->framework->getAdapter(System::class);
                $system->loadLanguageFile(substr($callback->getDomain(), 7), $original);
            }
        }
    }
}
