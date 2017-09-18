<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Persists the locale from the accept header or the request in the session.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class LocaleListener
{
    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var array
     */
    private $availableLocales;

    /**
     * Constructor.
     *
     * @param ScopeMatcher $scopeMatcher
     * @param array        $availableLocales
     */
    public function __construct(ScopeMatcher $scopeMatcher, $availableLocales)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->availableLocales = $availableLocales;
    }

    /**
     * Sets the default locale based on the request or session.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->scopeMatcher->isContaoRequest($event->getRequest())) {
            return;
        }

        $request = $event->getRequest();
        $request->attributes->set('_locale', $this->getLocale($request));
    }

    /**
     * Returns the locale from the request, the session or the HTTP header.
     *
     * @param Request $request
     *
     * @return string
     */
    private function getLocale(Request $request)
    {
        if (null !== $request->attributes->get('_locale')) {
            return $this->formatLocaleId($request->attributes->get('_locale'));
        }

        if (null !== ($session = $request->getSession()) && $session->has('_locale')) {
            return $session->get('_locale');
        }

        return $request->getPreferredLanguage($this->availableLocales);
    }

    /**
     * Formats a string to represent a locale ID.
     *
     * @param string $locale
     *
     * @return string
     *
     * @throw \InvalidArgumentException
     */
    private function formatLocaleId($locale)
    {
        if (!preg_match('/^[a-z]{2}([_-][a-z]{2})?$/i', $locale)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a supported locale.', $locale));
        }

        $values = preg_split('/-|_/', $locale);
        $locale = strtolower($values[0]);

        if (isset($values[1])) {
            $locale .= '_'.strtoupper($values[1]);
        }

        return $locale;
    }
}
