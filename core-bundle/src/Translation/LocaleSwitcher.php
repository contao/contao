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
use Symfony\Contracts\Translation\LocaleAwareInterface;

class LocaleSwitcher implements LocaleAwareInterface
{
    public function __construct(private SymfonyLocaleSwitcher $inner, private ContaoFramework $framework)
    {
    }

    public function setLocale(string $locale): void
    {
        $this->inner->setLocale($locale);
    }

    public function getLocale(): string
    {
        return $this->inner->getLocale();
    }

    public function runWithLocale(string $locale, callable $callback): mixed
    {
        $original = $this->getLocale();

        $this->inner->runWithLocale($locale, $callback);

        // Reload translations of previous language to $GLOBALS['TL_LANG']
        if ($original !== $locale && $callback instanceof TranslatorCallback && str_starts_with($callback->getDomain() ?? '', 'contao_')) {
            $system = $this->framework->getAdapter(System::class);
            $system->loadLanguageFile(substr($callback->getDomain(), 7), $original);
        }
    }

    public function reset(): void
    {
        $this->inner->reset();
    }
}
