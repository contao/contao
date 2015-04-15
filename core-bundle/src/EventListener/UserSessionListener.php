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
use Contao\CoreBundle\Session\Attribute\AttributeBagAdapter;
use Contao\FrontendUser;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

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
     * Constructor.
     *
     * @param SessionInterface $session
     */
    public function __construct(
        SessionInterface $session,
        Connection $connection
    ) {
        $this->session = $session;
        $this->connection = $connection;
    }

    /**
     * Checks whether the user has session data stored and passes it to the
     * session service if so.
     *
     * @param GetResponseEvent $event The event object
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->isFrontendMasterRequest($event)
            && !$this->isBackendMasterRequest($event)
        ) {
            return;
        }

        /** @var AttributeBagInterface $bag */
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
        if (!$this->isFrontendMasterRequest($event)
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
     * Stores the session for the back end.
     *
     * @param Request $request
     */
    private function storeBackendSession(Request $request)
    {
        if (!$this->checkShouldNotModifyBackendSession($request)) {
            $this->storeSession();
            return;
        }

        $key        = $request->query->has('popup') ? 'popupReferer' : 'referer';
        $refererId  = $request->attributes->get('_contao_referer_id');
        /** @var AttributeBagInterface $bag */
        $bag        = $this->getSessionBag();
        $refererOld = $bag->get('referer');
        $refererNew = [];

        if (!is_array($refererOld[$key])
            || !is_array($refererOld[$key][$refererId])
        ) {
            $refererOld[$key][$refererId]['last'] = '';
        }

        while (count($refererOld[$key]) >= 25) {
            array_shift($refererOld[$key]);
        }

        $ref = $request->query->get('ref');

        if ($ref != '' && isset($refererOld[$key][$ref])) {
            if (!isset($refererOld[$key][$refererId])) {
                $refererOld[$key][$refererId] = [];
            }

            $refererNew[$key][$refererId]         = array_merge(
                $refererOld[$key][$refererId],
                $refererOld[$key][$ref]
            );
            $refererNew[$key][$refererId]['last'] = $refererOld[$key][$ref]['current'];
        } elseif (count($refererOld[$key]) > 1) {
            $refererNew[$key][$refererId] = end($refererOld[$key]);
        }

        $refererNew[$key][$refererId]['current'] = $request->getRequestUri();

        $this->session->set('referer', $refererNew);

        $this->storeSession();
    }

    /**
     * Check if we should not modify the session in the back end.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function checkShouldNotModifyBackendSession(Request $request)
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
     * Stores the session for the front end.
     *
     * @param Request $request
     */
    private function storeFrontendSession(Request $request)
    {
        /** @var AttributeBagInterface $bag */
        $bag = $this->getSessionBag();

        $refererOld = $bag->get('referer');

        if (!$this->checkShouldNotModifyFrontendSession($request, $refererOld)) {
            $this->storeSession();
            return;
        }

        $refererNew = [
            'last'      => $refererOld['current'],
            'current'   => $request->getRequestUri()
        ];

        $this->session->set('referer', $refererNew);

        $this->storeSession();
    }

    /**
     * Check if we should not modify the session in the front end.
     *
     * @param Request $request
     * @param array   $refererOld
     *
     * @return bool
     */
    private function checkShouldNotModifyFrontendSession(
        Request $request,
        $refererOld = null
    ) {
        if (null === $refererOld) {
            return false;
        }

        if (!$request->query->has('pdf')
            && !$request->query->has('file')
            && !$request->query->has('id')
            && $refererOld['current'] !==  $request->getRequestUri()
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
        $user   = $this->getUserObject();
        $table  = $this->isFrontendScope() ? 'tl_member' : 'tl_user';

        $this->connection->prepare('UPDATE ' . $table . ' SET session=? WHERE id=?')
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
        // FIXME: Replace with security component
        return $this->isFrontendScope() ?
            FrontendUser::getInstance() :
            BackendUser::getInstance();
    }
}
