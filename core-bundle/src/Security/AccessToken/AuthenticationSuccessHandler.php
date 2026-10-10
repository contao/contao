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
        if (!$token->hasAttribute('access_token_id')) {
            return null;
        }

        $id = $token->getAttribute('access_token_id');

        $personalAccessToken = $this->entityManager->getRepository(PersonalAccessToken::class)->find($id);

        if (!$personalAccessToken instanceof PersonalAccessToken) {
            return null;
        }

        $personalAccessToken->setLastUsed(new \DateTimeImmutable());

        $this->entityManager->persist($personalAccessToken);
        $this->entityManager->flush();

        return null;
    }
}
