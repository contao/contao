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
        if (!$this->canModifyBackendSession($request)) {
            $this->storeSession();
            return;
        }

        $key        = $request->query->has('popup') ? 'popupReferer' : 'referer';
        $refererId  = $request->attributes->get('_contao_referer_id');
        $bag        = $this->getSessionBag();
        $refererOld = $this->prepareBackendReferer($bag->get($key), $refererId);
        $refererNew = [];

        $ref = $request->query->get('ref', '');

        if ('' !== $ref && isset($refererOld[$ref])) {
            if (!isset($refererOld[$refererId])) {
                $refererOld[$refererId] = [];
            }

            $refererNew[$refererId] = array_merge(
                $refererOld[$refererId],
                $refererOld[$ref]
            );
            $refererNew[$refererId]['last'] = $refererOld[$ref]['current'];
        } elseif (count($refererOld) > 1) {
            $refererNew[$refererId] = end($refererOld);
        }

        $refererNew[$refererId]['current'] = $request->getRequestUri();

        $bag->set($key, $refererNew);

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
            'current'   => $request->getRequestUri()
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
