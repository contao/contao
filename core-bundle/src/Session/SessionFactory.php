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

use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionFactory implements SessionFactoryInterface
{
    public const SESSION_BAGS = [
        'contao_backend' => '_contao_be_attributes',
        'contao_backend_popup' => '_contao_be_popup_attributes',
        'contao_frontend' => '_contao_fe_attributes',
    ];

    public function __construct(readonly private SessionFactoryInterface $inner)
    {
    }

    public function createSession(): SessionInterface
    {
        $session = $this->inner->createSession();

        foreach (self::SESSION_BAGS as $name => $storageKey) {
            $bag = new ArrayAttributeBag($storageKey);
            $bag->setName($name);

            $session->registerBag($bag);
        }

        return $session;
    }
}
