<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Csrf;

use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class MemoryTokenStorage implements TokenStorageInterface
{
    /**
     * @var array
     */
    private $tokens;

    /**
     * @var array
     */
    private $usedTokens = [];

    /**
     * {@inheritdoc}
     */
    public function getToken($tokenId): string
    {
        $this->assertInitialized();

        if (empty($this->tokens[$tokenId])) {
            throw new TokenNotFoundException(sprintf('The CSRF token ID "%s" does not exist.', $tokenId));
        }

        $this->usedTokens[$tokenId] = true;

        return $this->tokens[$tokenId];
    }

    /**
     * {@inheritdoc}
     */
    public function setToken($tokenId, $token): void
    {
        $this->assertInitialized();

        $this->usedTokens[$tokenId] = true;
        $this->tokens[$tokenId] = $token;
    }

    /**
     * {@inheritdoc}
     */
    public function hasToken($tokenId): bool
    {
        $this->assertInitialized();

        return !empty($this->tokens[$tokenId]);
    }

    /**
     * {@inheritdoc}
     */
    public function removeToken($tokenId): void
    {
        $this->assertInitialized();

        $this->usedTokens[$tokenId] = true;
        $this->tokens[$tokenId] = null;
    }

    /**
     * Initializes the storage.
     *
     * @param array $tokens
     */
    public function initialize(array $tokens): void
    {
        $this->tokens = $tokens;
    }

    /**
     * Returns all used tokens.
     *
     * @return array
     */
    public function getUsedTokens(): array
    {
        return array_intersect_key($this->tokens, $this->usedTokens);
    }

    /**
     * Checks if the storage is initialized.
     *
     * @throws \LogicException if the store was not initialized
     */
    private function assertInitialized(): void
    {
        if (null === $this->tokens) {
            throw new \LogicException('MemoryTokenStorage must not be accessed before it was initialized.');
        }
    }
}
