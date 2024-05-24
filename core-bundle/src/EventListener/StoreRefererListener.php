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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * @internal
 */
#[AsEventListener]
class StoreRefererListener
{
    public function __construct(
        private readonly Security $security,
        private readonly ScopeMatcher $scopeMatcher,
    ) {
    }

    /**
     * Stores the referer in the session.
     */
    public function __invoke(ResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isBackendMainRequest($event)) {
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
            $referers[$refererId] = [...$referers[$ref], ...$referers[$refererId]];
            $referers[$refererId]['last'] = $referers[$ref]['current'];
        }

        // Set new current referer
        $referers[$refererId]['current'] = $request->getRequestUri();

        $session->set($key, $referers);
    }

    private function canModifyBackendSession(Request $request): bool
    {
        return !$request->query->has('act')
            && !$request->query->has('key')
            && !$request->query->has('token')
            && !$request->query->has('state')
            && 'feRedirect' !== $request->query->get('do')
            && 'backend' === $request->attributes->get('_scope')
            && false !== $request->attributes->get('_store_referrer')
            && !$request->isXmlHttpRequest();
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function prepareBackendReferer(string $refererId, array|null $referers = null): array
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
}
