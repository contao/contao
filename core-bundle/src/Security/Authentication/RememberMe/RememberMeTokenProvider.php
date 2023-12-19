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
    private RememberMeRepository|null $repository = null;

    public function __construct(private readonly \Closure $repositoryClosure)
    {
    }

    public function loadTokenBySeries(string $series): PersistentToken
    {
        $rememberMe = $this->getRepository()->findBySeries($series);

        return new PersistentToken(
            $rememberMe->getClass(),
            $rememberMe->getUserIdentifier(),
            $rememberMe->getSeries(),
            $rememberMe->getValue(),
            \DateTime::createFromInterface($rememberMe->getLastUsed()),
        );
    }

    public function deleteTokenBySeries(string $series): void
    {
        $this->getRepository()->deleteBySeries($series);
    }

    public function updateToken(string $series, #[\SensitiveParameter] string $tokenValue, \DateTime $lastUsed): void
    {
        $rememberMe = $this->getRepository()->findBySeries($series);
        $rememberMe->setValue($tokenValue);
        $rememberMe->setLastUsed($lastUsed);

        $this->getRepository()->persist($rememberMe);
    }

    public function createNewToken(PersistentTokenInterface $token): void
    {
        $this->getRepository()->persist(
            new RememberMe(
                $token->getClass(),
                $token->getUserIdentifier(),
                $token->getSeries(),
                $token->getTokenValue(),
                $token->getLastUsed(),
            ),
        );
    }

    private function getRepository(): RememberMeRepository
    {
        if (!$this->repository) {
            $this->repository = ($this->repositoryClosure)();
        }

        return $this->repository;
    }
}
