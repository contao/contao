<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Framework\ScopeAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Stores the referer in the session.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class StoreRefererListener
{
    use ScopeAwareTrait;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var AuthenticationTrustResolverInterface
     */
    private $authenticationTrustResolver;

    /**
     * Constructor.
     *
     * @param SessionInterface                     $session
     * @param TokenStorageInterface                $tokenStorage
     * @param AuthenticationTrustResolverInterface $authenticationTrustResolver
     */
    public function __construct(SessionInterface $session, TokenStorageInterface $tokenStorage, AuthenticationTrustResolverInterface $authenticationTrustResolver)
    {
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->authenticationTrustResolver = $authenticationTrustResolver;
    }

    /**
     * Stores the referer in the session.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->isContaoMasterRequest($event)) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token || $this->authenticationTrustResolver->isAnonymous($token)) {
            return;
        }

        $request = $event->getRequest();

        if ($this->isBackendScope()) {
            $this->storeBackendReferer($request);
        } else {
            $this->storeFrontendReferer($request);
        }
    }

    /**
     * Stores the back end referer.
     *
     * @param Request $request
     */
    private function storeBackendReferer(Request $request)
    {
        if (!$this->canModifyBackendSession($request)) {
            return;
        }

        $key = $request->query->has('popup') ? 'popupReferer' : 'referer';
        $refererId = $request->attributes->get('_contao_referer_id');
        $referers = $this->prepareBackendReferer($refererId, $this->session->get($key));
        $ref = $request->query->get('ref', '');

        // Move current to last if the referer is in both the URL and the session
        if ('' !== $ref && isset($referers[$ref])) {
            $referers[$refererId]['last'] = $referers[$ref]['current'];
        }

        // Set new current referer
        $referers[$refererId]['current'] = $this->getRelativeRequestUri($request);

        $this->session->set($key, $referers);
    }

    /**
     * Checks if the back end session can be modified.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function canModifyBackendSession(Request $request)
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
     * Prepares the back end referer array.
     *
     * @param string     $refererId
     * @param array|null $referers
     *
     * @return array
     */
    private function prepareBackendReferer($refererId, array $referers = null)
    {
        if (!is_array($referers)) {
            $referers = [];
        }

        if (!isset($referers[$refererId]) || !is_array($referers[$refererId])) {
            $referers[$refererId] = ['last' => ''];
        }

        // Make sure we never have more than 25 different referer URLs
        while (count($referers) >= 25) {
            array_shift($referers);
        }

        return $referers;
    }

    /**
     * Stores the front end referer.
     *
     * @param Request $request
     */
    private function storeFrontendReferer(Request $request)
    {
        $refererOld = $this->session->get('referer');

        if (!$this->canModifyFrontendSession($request, $refererOld)) {
            return;
        }

        $refererNew = [
            'last' => (string) $refererOld['current'],
            'current' => $this->getRelativeRequestUri($request),
        ];

        $this->session->set('referer', $refererNew);
    }

    /**
     * Checks if the front end session can be modified.
     *
     * @param Request    $request
     * @param array|null $referer
     *
     * @return bool
     */
    private function canModifyFrontendSession(Request $request, array $referer = null)
    {
        return (null !== $referer)
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
     *
     * @param Request $request
     *
     * @return string
     */
    private function getRelativeRequestUri(Request $request)
    {
        return (string) substr($request->getRequestUri(), strlen($request->getBasePath()) + 1);
    }
}
