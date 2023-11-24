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

use Contao\CoreBundle\Intl\Locales;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Util\LocaleUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\LocaleAwareInterface;

/**
 * @internal
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    private readonly array $availableLocales;

    public function __construct(
        private readonly LocaleAwareInterface $translator,
        private readonly ScopeMatcher $scopeMatcher,
        Locales $locales,
    ) {
        $this->availableLocales = $locales->getEnabledLocaleIds();
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
     * Sets the translator locale to the preferred browser language.
     */
    public function setTranslatorLocale(RequestEvent $event): void
    {
        $this->translator->setLocale($event->getRequest()->getPreferredLanguage($this->availableLocales));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                // The priority must be lower than the one of the Symfony route listener (defaults to 32)
                // and higher than the Symfony locale listener (defaults to 16)
                ['onKernelRequest', 20],
                ['setTranslatorLocale', 100],
            ],
        ];
    }

    /**
     * Returns the locale from the request or the HTTP header.
     */
    private function getLocale(Request $request): string
    {
        if (null !== $request->attributes->get('_locale')) {
            return LocaleUtil::formatAsLocale($request->attributes->get('_locale'));
        }

        return $request->getPreferredLanguage($this->availableLocales);
    }
}
