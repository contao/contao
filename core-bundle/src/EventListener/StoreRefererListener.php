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
use Contao\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Core\Security;

/**
 * @internal
 */
class StoreRefererListener
{
    /**
     * @var Security
     */
    private $security;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    public function __construct(Security $security, ScopeMatcher $scopeMatcher)
    {
        $this->security = $security;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * Stores the referer in the session.
     */
    public function __invoke(ResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isContaoMasterRequest($event)) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->isMethod(Request::METHOD_GET)) {
            return;
        }

        $response = $event->getResponse();

        if (200 !== $response->getStatusCode()) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        if ($this->scopeMatcher->isBackendRequest($request)) {
            $this->storeBackendReferer($request);
        } else {
            $this->storeFrontendReferer($request);
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function storeBackendReferer(Request $request): void
    {
        if (!$this->canModifyBackendSession($request)) {
            return;
        }

        if (!$request->hasSession()) {
            throw new \RuntimeException('The request did not contain a session.');
        }

        $session = $request->getSession();
        $key = $request->query->has('popup') ? 'popupReferer' : 'referer';
        $refererId = $request->attributes->get('_contao_referer_id');
        $referers = $this->prepareBackendReferer($refererId, $session->get($key));
        $ref = $request->query->get('ref', '');

        // Move current to last if the referer is in both the URL and the session
        if ('' !== $ref && isset($referers[$ref])) {
            $referers[$refererId] = array_merge($referers[$refererId], $referers[$ref]);
            $referers[$refererId]['last'] = $referers[$ref]['current'];
        }

        // Set new current referer
        $referers[$refererId]['current'] = $this->getRelativeRequestUri($request);

        $session->set($key, $referers);
    }

    private function canModifyBackendSession(Request $request): bool
    {
        return !$request->query->has('act')
            && !$request->query->has('key')
            && !$request->query->has('token')
            && !$request->query->has('state')
            && 'feRedirect' !== $request->query->get('do')
            && 'contao_backend' === $request->attributes->get('_route')
            && !$request->isXmlHttpRequest()
        ;
    }

    /**
     * @return array<string,array<string,string>>
     */
    private function prepareBackendReferer(string $refererId, array $referers = null): array
    {
        if (!\is_array($referers)) {
            $referers = [];
        }

        if (!isset($referers[$refererId]) || !\is_array($referers[$refererId])) {
            $referers[$refererId] = end($referers) ?: ['last' => ''];
        }

        // Make sure we never have more than 25 different referer URLs
        while (\count($referers) >= 25) {
            array_shift($referers);
        }

        return $referers;
    }

    /**
     * @throws \RuntimeException
     */
    private function storeFrontendReferer(Request $request): void
    {
        if (!$request->hasSession()) {
            throw new \RuntimeException('The request did not contain a session.');
        }

        $session = $request->getSession();
        $refererOld = $session->get('referer');

        if (!$this->canModifyFrontendSession($request, $refererOld)) {
            return;
        }

        $refererNew = [
            'last' => (string) $refererOld['current'],
            'current' => $this->getRelativeRequestUri($request),
        ];

        $session->set('referer', $refererNew);
    }

    private function canModifyFrontendSession(Request $request, array $referer = null): bool
    {
        return null !== $referer
            && !$request->query->has('pdf')
            && !$request->query->has('file')
            && !$request->query->has('id')
            && isset($referer['current'])
            && 'contao_frontend' === $request->attributes->get('_route')
            && $this->getRelativeRequestUri($request) !== $referer['current']
            && !$request->isXmlHttpRequest()
        ;
    }

    /**
     * Returns the current request URI relative to the base path.
     */
    private function getRelativeRequestUri(Request $request): string
    {
        return (string) substr($request->getRequestUri(), \strlen($request->getBasePath()) + 1);
    }
}
