<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\AccessToken;

use Contao\CoreBundle\Entity\PersonalAccessToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * @internal
 */
class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): null
    {
        $personalAccessToken = $request->attributes->get('_access_token');

        if (!$personalAccessToken instanceof PersonalAccessToken) {
            return null;
        }

        // Do not update the database on every request
        if ($personalAccessToken->getLastUsed() >= new \DateTimeImmutable('-1 minute')) {
            return null;
        }

        $personalAccessToken->setLastUsed(new \DateTimeImmutable());

        $this->entityManager->persist($personalAccessToken);
        $this->entityManager->flush();

        return null;
    }
}
