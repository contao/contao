<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Authentication;

use Contao\CoreBundle\Entity\PersonalAccessToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AccessTokenSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): null
    {
        $personalAccessToken = $token->getAttribute('access_token');

        if (!$personalAccessToken instanceof PersonalAccessToken) {
            return null;
        }

        $personalAccessToken->setLastUsed(new \DateTimeImmutable());

        $this->entityManager->persist($personalAccessToken);
        $this->entityManager->flush();

        return null;
    }
}
