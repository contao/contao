<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Stores the referer in the session.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class StoreRefererListener
{
    use ScopeAwareTrait;
    use UserAwareTrait;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * Constructor.
     *
     * @param SessionInterface $session The session object
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Stores the referer in the session.
     *
     * @param FilterResponseEvent $event The event object
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->hasUser() || !$this->isContaoMasterRequest($event)) {
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
     * @param Request $request The request object
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
     * @param Request $request The request object
     *
     * @return bool True if the back end session can be modified
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
     * @param string     $refererId The referer ID
     * @param array|null $referers  The old referer data
     *
     * @return array The back end referer URLs
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
     * @param Request $request The request object
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
     * @param Request    $request The request object
     * @param array|null $referer The referer array
     *
     * @return bool True if the front end session can be modified
     */
    private function canModifyFrontendSession(Request $request, array $referer = null)
    {
        return (null !== $referer)
            && !$request->query->has('pdf')
            && !$request->query->has('file')
            && !$request->query->has('id')
            && isset($referer['current'])
            && 'contao_frontend' === $request->attributes->get('_route')
            && $referer['current'] !== $this->getRelativeRequestUri($request)
            && !$request->isXmlHttpRequest()
        ;
    }

    /**
     * Returns the current request URI relative to the base path.
     *
     * @param Request $request The request object
     *
     * @return string The relative request URI
     */
    private function getRelativeRequestUri(Request $request)
    {
        return (string) substr($request->getRequestUri(), strlen($request->getBasePath()) + 1);
    }
}
