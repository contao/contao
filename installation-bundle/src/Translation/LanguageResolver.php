<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Translation;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Maps the accepted languages to the available locales.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class LanguageResolver
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $translationsDir;

    /**
     * Constructor.
     *
     * @param RequestStack $requestStack    The request stack
     * @param string       $translationsDir The translations directory
     */
    public function __construct(RequestStack $requestStack, $translationsDir)
    {
        $this->requestStack    = $requestStack;
        $this->translationsDir = $translationsDir;
    }

    /**
     * Returns the first available locale.
     *
     * @return string The locale
     */
    public function getLocale()
    {
        foreach ($this->getAcceptedLocales() as $locale) {
            if (file_exists($this->translationsDir . '/messages.' . $locale . '.xlf')) {
                return $locale;
            }
        }

        return 'en';
    }

    /**
     * Returns the first eight accepted locales.
     *
     * @return array The accepted locales
     */
    private function getAcceptedLocales()
    {
        $accepted = [];
        $locales  = [];

        // The implementation differs from the original implementation and also works with .jp browsers
        preg_match_all(
            '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
            $this->requestStack->getCurrentRequest()->headers->get('accept-language'),
            $accepted
        );

        // Remove all invalid locales
        foreach ($accepted[1] as $v) {
            $chunks = explode('-', $v);

            // Language plus dialect, e.g. "en-US" or "fr-FR"
            if (isset($chunks[1])) {
                $locale = $chunks[0] . '-' . strtoupper($chunks[1]);

                if (preg_match('/^[a-z]{2}(\-[A-Z]{2})?$/', $locale)) {
                    $locales[] = $locale;
                }
            }

            $locale = $chunks[0];

            // Language only, e.g. "en" or "fr"
            if (preg_match('/^[a-z]{2}$/', $locale)) {
                $locales[] = $locale;
            }
        }

        return array_slice(array_unique($locales), 0, 8);
    }
}
