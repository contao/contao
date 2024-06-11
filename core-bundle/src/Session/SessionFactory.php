<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Session;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionFactory implements SessionFactoryInterface
{
    public const SESSION_BAGS = [
        'contao_backend' => '_contao_be_attributes',
        'contao_frontend' => '_contao_fe_attributes',
    ];

    public function __construct(
        readonly private SessionFactoryInterface $inner,
        readonly private RequestStack $requestStack,
        readonly private ScopeMatcher $scopeMatcher,
    ) {
    }

    public function createSession(): SessionInterface
    {
        $session = $this->inner->createSession();
        $request = $this->requestStack->getMainRequest();

        foreach (self::SESSION_BAGS as $name => $storageKey) {
            // Store the 'contao_backend' session bag under a different storage key for the
            // back end popup (#7176).
            if ('contao_backend' === $name && $this->scopeMatcher->isBackendRequest($request) && $request->query->has('popup')) {
                $storageKey .= '_popup';
            }

            $bag = new ArrayAttributeBag($storageKey);
            $bag->setName($name);

            $session->registerBag($bag);
        }

        return $session;
    }
}
