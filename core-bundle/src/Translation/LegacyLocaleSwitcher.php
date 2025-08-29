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
use Contao\CoreBundle\Util\LocaleUtil;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Updates the TL_LANGUAGE superglobal, whenever the locale is set or switched.
 *
 * @internal
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 6.
 */
class LegacyLocaleSwitcher implements LocaleAwareInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function setLocale(string $locale): void
    {
        if ($this->framework->isInitialized()) {
            $GLOBALS['TL_LANGUAGE'] = LocaleUtil::formatAsLanguageTag($locale);
        }
    }

    public function getLocale(): string
    {
        if ($this->framework->isInitialized() && isset($GLOBALS['TL_LANGUAGE'])) {
            return LocaleUtil::formatAsLocale($GLOBALS['TL_LANGUAGE']);
        }

        return $this->translator->getLocale();
    }
}
