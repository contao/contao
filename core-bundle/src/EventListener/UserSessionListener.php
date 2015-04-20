<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\BackendUser;
use Contao\FrontendUser;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Stores and restores the user session.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class UserSessionListener extends ScopeAwareListener
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * Constructor.
     *
     * @param SessionInterface      $session      The session object
     * @param Connection            $connection   The database connection
     * @param TokenStorageInterface $tokenStorage The token storage object
     */
    public function __construct(SessionInterface $session, Connection $connection, TokenStorageInterface $tokenStorage)
    {
        $this->session      = $session;
        $this->connection   = $connection;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Replaces the current session data with the stored session data.
     *
     * @param GetResponseEvent $event The event object
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->hasUser() && !$this->isFrontendMasterRequest($event) && !$this->isBackendMasterRequest($event)) {
            return;
        }

        $session = $this->getUserObject()->session;

        if (is_array($session)) {
            $this->getSessionBag()->replace($session);
        }
    }

    /**
     * Writes the current session data to the database.
     *
     * @param FilterResponseEvent $event The event object
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->hasUser() && !$this->isFrontendMasterRequest($event) && !$this->isBackendMasterRequest($event)) {
            return;
        }

        $request = $event->getRequest();

        if ($this->isBackendScope()) {
            $this->storeBackendSession($request);
        } else {
            $this->storeFrontendSession($request);
        }
    }

    /**
     * Checks if there is an authenticated user.
     *
     * @return bool True if there is an authenticated user
     */
    private function hasUser()
    {
        $user = $this->tokenStorage->getToken();

        if (null === $user) {
            return false;
        }

        if ($user instanceof AnonymousToken) {
            return false;
        }

        return true;
    }

    /**
     * Stores the back end session data.
     *
     * @param Request $request The request object
     */
    private function storeBackendSession(Request $request)
    {
        // Update the referer URL
        if ($this->canModifyBackendSession($request)) {
            $key       = $request->query->has('popup') ? 'popupReferer' : 'referer';
            $refererId = $request->attributes->get('_contao_referer_id');
            $bag       = $this->getSessionBag();
            $referer   = $this->prepareBackendReferer($refererId, $bag->get($key));
            $ref       = $request->query->get('ref', '');

            // Move current to last if the referer is in both the URL and the session
            if ('' !== $ref && isset($referer[$ref])) {
                $referer[$refererId]['last'] = $referer[$ref]['current'];
            }

            // Set new current referer
            $referer[$refererId]['current'] = $this->getRelativeRequestUri($request);

            $bag->set($key, $referer);
        }

        $this->storeSession();
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
        if (!$request->query->has('act')
            && !$request->query->has('key')
            && !$request->query->has('token')
            && !$request->query->has('state')
            && 'feRedirect' !== $request->query->get('do')
            && !$request->isXmlHttpRequest()
        ) {
            return true;
        }

        return false;
    }

    /**
     * Prepares the back end referer array.
     *
     * @param string $refererId  The referer ID
     * @param array  $refererOld The old referer data
     *
     * @return array The back end referer URLs
     */
    private function prepareBackendReferer($refererId, $refererOld = null)
    {
        if (!is_array($refererOld) || !is_array($refererOld[$refererId])) {
            $refererOld                     = [];
            $refererOld[$refererId]         = [];
            $refererOld[$refererId]['last'] = '';
        }

        // Make sure we never have more than 25 different referer URLs
        while (count($refererOld) >= 25) {
            array_shift($refererOld);
        }

        return $refererOld;
    }

    /**
     * Stores the front end session.
     *
     * @param Request $request The request object
     */
    private function storeFrontendSession(Request $request)
    {
        $bag        = $this->getSessionBag();
        $refererOld = $bag->get('referer');

        // Update the referer URL
        if ($this->canModifyFrontendSession($request, $refererOld)) {
            $refererNew = [
                'last'      => (string) $refererOld['current'],
                'current'   => $this->getRelativeRequestUri($request)
            ];

            $bag->set('referer', $refererNew);
        }

        $this->storeSession();
    }

    /**
     * Checks if the front end session can be modified.
     *
     * @param Request $request    The request object
     * @param array   $refererOld The old referer data
     *
     * @return bool True if the front end session can be modified
     */
    private function canModifyFrontendSession(Request $request, $refererOld = null)
    {
        if (null !== $refererOld
            && !$request->query->has('pdf')
            && !$request->query->has('file')
            && !$request->query->has('id')
            && isset($refererOld['current'])
            && $refererOld['current'] !== $this->getRelativeRequestUri($request)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Returns the session bag.
     *
     * @return AttributeBagInterface The session bag
     */
    private function getSessionBag()
    {
        if ($this->isBackendScope()) {
            $bag = 'contao_backend';
        } else {
            $bag = 'contao_frontend';
        }

        return $this->session->getBag($bag);
    }

    /**
     * Stores the session data in the database.
     */
    private function storeSession()
    {
        $user = $this->getUserObject();

        $this->connection
            ->prepare('UPDATE ' . $user->getTable() . ' SET session=? WHERE id=?')
            ->execute([serialize($this->getSessionBag()->all()), $user->id])
        ;
    }

    /**
     * Returns the user object depending on the container scope.
     *
     * @return FrontendUser|BackendUser The user object
     */
    private function getUserObject()
    {
        return $this->tokenStorage->getToken()->getUser();
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
