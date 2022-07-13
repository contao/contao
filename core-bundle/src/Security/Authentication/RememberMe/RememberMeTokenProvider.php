<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Authentication\RememberMe;

use Contao\CoreBundle\Entity\RememberMe;
use Contao\CoreBundle\Repository\RememberMeRepository;
use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentToken;
use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentTokenInterface;
use Symfony\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface;

class RememberMeTokenProvider implements TokenProviderInterface
{
    public function __construct(private RememberMeRepository $repository)
    {
    }

    public function loadTokenBySeries(string $series): PersistentToken
    {
        $rememberMe = $this->repository->findBySeries($series);

        return new PersistentToken(
            $rememberMe->getClass(),
            $rememberMe->getUserIdentifier(),
            $rememberMe->getSeries(),
            $rememberMe->getValue(),
            \DateTime::createFromInterface($rememberMe->getLastUsed())
        );
    }

    public function deleteTokenBySeries(string $series): void
    {
        $this->repository->deleteBySeries($series);
    }

    public function updateToken(string $series, string $tokenValue, \DateTime $lastUsed): void
    {
        $rememberMe = $this->repository->findBySeries($series);
        $rememberMe->setValue($tokenValue);
        $rememberMe->setLastUsed($lastUsed);

        $this->repository->persist($rememberMe);
    }

    public function createNewToken(PersistentTokenInterface $token): void
    {
        $this->repository->persist(
            new RememberMe(
                $token->getClass(),
                $token->getUserIdentifier(),
                $token->getSeries(),
                $token->getTokenValue(),
                $token->getLastUsed()
            )
        );
    }
}
