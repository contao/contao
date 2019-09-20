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

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class LocaleListener
{
    /**
     * @var LocaleAwareInterface
     */
    private $translator;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var array
     */
    private $availableLocales;

    public function __construct(LocaleAwareInterface $translator, ScopeMatcher $scopeMatcher, array $availableLocales)
    {
        $this->translator = $translator;
        $this->scopeMatcher = $scopeMatcher;
        $this->availableLocales = $availableLocales;
    }

    /**
     * Sets the translator locale to the preferred browser language.
     */
    public function setTranslatorLocale(RequestEvent $event): void
    {
        $this->translator->setLocale($event->getRequest()->getPreferredLanguage($this->availableLocales));
    }

    /**
     * Adds the default locale as request attribute.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->scopeMatcher->isContaoRequest($event->getRequest())) {
            return;
        }

        $request = $event->getRequest();
        $request->attributes->set('_locale', $this->getLocale($request));
    }

    /**
     * Returns the locale from the request or the HTTP header.
     */
    private function getLocale(Request $request): string
    {
        if (null !== $request->attributes->get('_locale')) {
            return $this->formatLocaleId($request->attributes->get('_locale'));
        }

        return $request->getPreferredLanguage($this->availableLocales);
    }

    private function formatLocaleId(string $locale): string
    {
        if (!preg_match('/^[a-z]{2}([_-][a-z]{2})?$/i', $locale)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a supported locale.', $locale));
        }

        $values = preg_split('/[_-]/', $locale);
        $locale = strtolower($values[0]);

        if (isset($values[1])) {
            $locale .= '_'.strtoupper($values[1]);
        }

        return $locale;
    }
}
