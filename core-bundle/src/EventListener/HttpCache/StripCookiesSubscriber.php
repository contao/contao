<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\HttpCache;

use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class StripCookiesSubscriber implements EventSubscriberInterface
{
    private const DENY_LIST = [
        // Contao Manager
        'contao_manager_auth',

        // Modals are always for JS only
        '(.*)?modal(.*)?',

        // Google Analytics
        // https://developers.google.com/analytics/devguides/collection/analyticsjs/cookie-usage
        '_ga.*',
        '_gid',
        '_dc_gtm_.+',
        'AMP_TOKEN',
        '__utm.+',

        // Google Conversion Linker
        // https://support.google.com/tagmanager/answer/7549390
        '_gcl.*',

        // Matomo
        // https://matomo.org/faq/general/faq_146/
        '_pk_id.*',
        '_pk_ref.*',
        '_pk_ses.*',
        '_pk_cvar.*',
        '_pk_hsr.*',

        // Cloudflare
        '__cfduid',
        'cf_clearance',
        'cf_use_ob',
        'cf_ob_info',

        // Facebook Pixel
        '_fbp',

        // Blackfire
        '__blackfire',

        // Tideways
        'TIDEWAYS_SESSION',

        // Litespeed HTTP2 Smart Push
        'ls_smartpush',

        // Osano Cookie Consent
        'cookieconsent_status',

        // Cookiebot Cookie Consent
        'CookieConsent',

        // Cypress
        '__cypress_initial',
    ];

    private array $removeFromDenyList = [];

    public function __construct(private readonly array $allowList = [])
    {
    }

    public function getAllowList(): array
    {
        return $this->allowList;
    }

    public function removeFromDenyList(array $removeFromDenyList): self
    {
        $this->removeFromDenyList = $removeFromDenyList;

        return $this;
    }

    public function preHandle(CacheEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->cookies->count()) {
            return;
        }

        // Use a custom allow list if present, otherwise use the default deny list
        if ($this->allowList) {
            $this->filterCookies($request, $this->allowList);
        } else {
            $this->filterCookies($request, $this->removeFromDenyList, self::DENY_LIST);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_HANDLE => 'preHandle',
        ];
    }

    private function filterCookies(Request $request, array $allowList = [], array $denyList = []): void
    {
        // Remove cookies that match the deny list or all if no deny list was set
        $removeCookies = preg_grep(
            '/^(?:'.implode(')$|^(?:', $denyList ?: ['.*']).')$/i',
            array_keys($request->cookies->all()),
        );

        // Do not remove cookies that match the allow list
        $removeCookies = preg_grep('/^(?:'.implode(')$|^(?:', $allowList).')$/i', $removeCookies, PREG_GREP_INVERT);

        foreach ($removeCookies as $name) {
            $request->cookies->remove($name);
        }
    }
}
