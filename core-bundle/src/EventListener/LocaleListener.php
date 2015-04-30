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
     * @var string
     */
    private $defaultLocale;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * Constructor.
     *
     * @param string $defaultLocale The default locale
     * @param string $rootDir       The kernel root directory
     */
    public function __construct($defaultLocale, $rootDir)
    {
        $this->defaultLocale = $defaultLocale;
        $this->rootDir       = $rootDir;

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
            $locale = $this->getPreferredLocale($request);
        }

        $this->saveLocale($request, $locale);
    }

    /**
     * Gets the preferred locale ID from browser Accept-Language headers.
     *
     * @param Request $request The request object
     *
     * @return string The preferred locale ID
     */
    private function getPreferredLocale(Request $request)
    {
        $finder = Finder::create()
            ->directories()
            ->depth(0)
            ->in([
                __DIR__ . '../Resources/contao/languages',
                $this->rootDir . '/Resources/contao/languages'
            ]);

        $languages = array_values(
            array_map(
                function(SplFileInfo $file) {
                    return $file->getFilename();
                },
                iterator_to_array($finder)
            )
        );

        // The default locale must be the first supported language (also see contao/core#6533)
        array_unshift($languages, $this->defaultLocale);

        return $request->getPreferredLanguage(array_unique($languages));
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


    private function saveLocale(Request $request, $locale)
    {
        $request->attributes->set('_locale', $locale);

        if ($request->hasSession()) {
            $request->getSession()->set('_locale', $locale);
        }
    }
}
