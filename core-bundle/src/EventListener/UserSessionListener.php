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
 * Stores the session data of back end and front end users and restores it on
 * the next login so the last application state can be restored.
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
     * @param SessionInterface $session
     */
    public function __construct(
        SessionInterface $session,
        Connection $connection,
        TokenStorageInterface $tokenStorage
    ) {
        $this->session      = $session;
        $this->connection   = $connection;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Checks whether the user has session data stored and passes it to the
     * session service if so.
     *
     * @param GetResponseEvent $event The event object
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->hasUser()
            && !$this->isFrontendMasterRequest($event)
            && !$this->isBackendMasterRequest($event)
        ) {
            return;
        }

        $bag     = $this->getSessionBag();
        $session = $this->getUserObject()->session;

        // Restore session
        if (is_array($session)) {
            $bag->replace($session);
        }
    }

    /**
     * Checks whether there is something to store in the user session depending
     * on the container scope.
     *
     * @param FilterResponseEvent $event The event object
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->hasUser()
            && !$this->isFrontendMasterRequest($event)
            && !$this->isBackendMasterRequest($event)
        ) {
            return;
        }

        $request = $event->getRequest();

        if ($this->isBackendScope()) {
            $this->storeBackendSession($request);
            return;
        }

        $this->storeFrontendSession($request);
    }

    /**
     * Check if a user is authenticated
     *
     * @return bool
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
     * Stores the session for the back end.
     *
     * @param Request $request
     */
    private function storeBackendSession(Request $request)
    {
        if (!$this->canModifyBackendSession($request)) {
            $this->storeSession();
            return;
        }

        $key        = $request->query->has('popup') ? 'popupReferer' : 'referer';
        $refererId  = $request->attributes->get('_contao_referer_id');
        $bag        = $this->getSessionBag();
        $referer    = $this->prepareBackendReferer($bag->get($key), $refererId);

        $ref = $request->query->get('ref', '');

        // If the referer param is in the URL and in the session,
        // current is moved to last
        if ('' !== $ref && isset($referer[$ref])) {
            $referer[$refererId]['last'] = $referer[$ref]['current'];
        }

        // Set new current referer
        $referer[$refererId]['current'] = $this->getRelativeRequestUri($request);

        $bag->set($key, $referer);

        $this->storeSession();
    }

    /**
     * Check if we should can modify the session in the back end.
     *
     * @param Request $request
     *
     * @return bool
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
     * @param array|null $refererOld
     * @param string     $refererId
     *
     * @return array
     */
    private function prepareBackendReferer($refererOld = null, $refererId)
    {
        if (!is_array($refererOld)
            || !is_array($refererOld[$refererId])
        ) {
            $refererOld = [];
            $refererOld[$refererId] = [];
            $refererOld[$refererId]['last'] = '';
        }

        // Make sure we never have more than 25 different referer ids
        while (count($refererOld) >= 25) {
            array_shift($refererOld);
        }

        return $refererOld;
    }

    /**
     * Stores the session for the front end.
     *
     * @param Request $request
     */
    private function storeFrontendSession(Request $request)
    {
        $bag = $this->getSessionBag();

        $refererOld = $bag->get('referer');

        if (!$this->canModifyFrontendSession($request, $refererOld)) {
            $this->storeSession();
            return;
        }

        $refererNew = [
            'last'      => $refererOld['current'],
            'current'   => $this->getRelativeRequestUri($request)
        ];

        $this->session->set('referer', $refererNew);

        $this->storeSession();
    }

    /**
     * Check if we can modify the session in the front end.
     *
     * @param Request $request
     * @param array   $refererOld
     *
     * @return bool
     */
    private function canModifyFrontendSession(
        Request $request,
        $refererOld = null
    ) {
        if (null === $refererOld) {
            return false;
        }

        if (!$request->query->has('pdf')
            && !$request->query->has('file')
            && !$request->query->has('id')
            && $refererOld['current'] !==  $this->getRelativeRequestUri($request)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Gets the session bag.
     *
     * @return AttributeBagInterface
     */
    private function getSessionBag()
    {
        $bag = 'contao_backend';

        if ($this->isFrontendScope()) {
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

        $this->connection->prepare('UPDATE ' . $user->getTable() . ' SET session=? WHERE id=?')
            ->execute([
                    serialize($this->getSessionBag()->all()),
                    $user->id
                ]
            );
    }

    /**
     * Gets the User instance depending on the container scope.
     *
     * @return  FrontendUser|BackendUser
     */
    private function getUserObject()
    {
        return $this->tokenStorage->getToken()->getUser();
    }

    /**
     * Gets the current request URI relative to the base path
     *
     * @param   Request $request
     *
     * @return  string
     */
    private function getRelativeRequestUri(Request $request)
    {
        return (string) substr(
            $request->getRequestUri(),
            strlen($request->getBasePath()) + 1
        );
    }
}
