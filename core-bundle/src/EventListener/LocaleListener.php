<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Makes sure the locale is available in request and persisted in the session.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class LocaleListener extends ScopeAwareListener
{
    /**
     * @var array
     */
    private $availableLocales;

    /**
     * Constructor.
     *
     * @param array $availableLocales The locales available in the system
     */
    public function __construct($availableLocales)
    {
        $this->availableLocales = $availableLocales;
    }

    /**
     * Set the default locale based on the request or session.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->isContaoScope()) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->getSession();

        if ($request->attributes->has('_locale')) {
            $locale = $this->formatLocaleID($request->attributes->get('_locale'));
        } elseif (null !== $session && $session->has('_locale')) {
            $locale = $session->get('_locale');
        } else {
            $locale = $request->getPreferredLanguage($this->availableLocales);
        }

        $this->saveLocale($request, $locale);
    }

    /**
     * Format a string to represent a locale ID.
     *
     * @param $locale
     *
     * @return string
     */
    private function formatLocaleID($locale)
    {
        $values = preg_split('/-|_/', $locale);

        if (count($values) > 2 || strlen($values[0]) > 2 || (isset($values[1]) && strlen($values[1]) > 2)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a supported locale.', $locale));
        }

        $locale = strtolower($values[0]);

        if (isset($values[1])) {
            $locale .= '_' . strtoupper($values[1]);
        }

        return $locale;
    }

    /**
     * Saves the locale in the request attributes and the session (if available).
     *
     * @param Request $request
     * @param string  $locale
     */
    private function saveLocale(Request $request, $locale)
    {
        $request->attributes->set('_locale', $locale);

        if ($request->hasSession()) {
            $request->getSession()->set('_locale', $locale);
        }
    }

    /**
     * Creates an instance of LocaleListener with available languages from ContaoCoreBundle and app/Resources.
     *
     * @param string $defaultLocale The default locale
     * @param string $rootDir       The kernel root directory
     *
     * @return static A new instance of LocaleListener
     */
    public static function createWithLocales($defaultLocale, $rootDir)
    {
        $dirs = [__DIR__ . '/../Resources/contao/languages'];

        if (is_dir($rootDir . '/Resources/contao/languages')) {
            $dirs[] = $rootDir . '/Resources/contao/languages';
        }

        $finder = Finder::create()
                        ->directories()
                        ->depth(0)
                        ->in($dirs);

        $languages = array_values(
            array_map(
                function(SplFileInfo $file) {
                    return $file->getFilename();
                },
                iterator_to_array($finder)
            )
        );

        // The default locale must be the first supported language (also see contao/core#6533)
        array_unshift($languages, $defaultLocale);

        return new static(array_unique($languages));
    }
}
