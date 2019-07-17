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
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Translation\TranslatorInterface;

class LocaleListener
{
    /**
     * @var TranslatorInterface
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

    public function __construct(TranslatorInterface $translator, ScopeMatcher $scopeMatcher, array $availableLocales)
    {
        $this->translator = $translator;
        $this->scopeMatcher = $scopeMatcher;
        $this->availableLocales = $availableLocales;
    }

    /**
     * Adds the default locale as request attribute.
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isContaoRequest($event->getRequest())) {
            return;
        }

        $request = $event->getRequest();
        $request->attributes->set('_locale', $this->getLocale($request));
    }

    /**
     * Sets the locale of the request and translator so pretty error screens
     * are translated into the preferred user language.
     */
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $locale = $request->attributes->get('_locale') ?? $this->getLocale($request);

        $request->setLocale($locale);
        $this->translator->setLocale($locale);
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

        $values = preg_split('/-|_/', $locale);
        $locale = strtolower($values[0]);

        if (isset($values[1])) {
            $locale .= '_'.strtoupper($values[1]);
        }

        return $locale;
    }
}
