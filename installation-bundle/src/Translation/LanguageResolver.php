<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Translation;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\RequestStack;

class LanguageResolver
{
    private RequestStack $requestStack;
    private string $translationsDir;

    public function __construct(RequestStack $requestStack, string $translationsDir)
    {
        $this->requestStack = $requestStack;
        $this->translationsDir = $translationsDir;
    }

    /**
     * Returns the first available locale.
     */
    public function getLocale(): string
    {
        foreach ($this->getAcceptedLocales() as $locale) {
            if (file_exists(Path::join($this->translationsDir, "messages.$locale.xlf"))) {
                return $locale;
            }
        }

        return 'en';
    }

    /**
     * Returns the first eight accepted locales.
     *
     * @return array<string>
     */
    private function getAcceptedLocales(): array
    {
        $accepted = [];
        $locales = [];

        // The implementation differs from the original implementation and also works with .jp browsers
        preg_match_all(
            '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.\d+))?/i',
            $this->requestStack->getCurrentRequest()->headers->get('accept-language'),
            $accepted
        );

        // Remove all invalid locales
        foreach ($accepted[1] as $v) {
            $chunks = explode('-', $v);

            // Language plus dialect, e.g. "en-US" or "fr-FR"
            if (isset($chunks[1])) {
                $locale = $chunks[0].'-'.strtoupper($chunks[1]);

                if (preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $locale)) {
                    $locales[] = $locale;
                }
            }

            // Language only, e.g. "en" or "fr"
            if (preg_match('/^[a-z]{2}$/', $chunks[0])) {
                $locales[] = $chunks[0];
            }
        }

        return \array_slice(array_unique($locales), 0, 8);
    }
}
