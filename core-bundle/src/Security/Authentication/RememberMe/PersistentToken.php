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

use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentTokenInterface;

class PersistentToken implements PersistentTokenInterface
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $series;

    /**
     * @var string
     */
    private $tokenValue;

    /**
     * @var \DateTime
     */
    private $lastUsed;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(string $class, string $username, string $series, string $tokenValue, \DateTime $lastUsed)
    {
        if ('' === $class) {
            throw new \InvalidArgumentException('The class must not be empty.');
        }

        if ('' === $username) {
            throw new \InvalidArgumentException('The username must not be empty.');
        }

        if ('' === $series) {
            throw new \InvalidArgumentException('The series must not be empty.');
        }

        if ('' === $tokenValue) {
            throw new \InvalidArgumentException('The token value must not be empty.');
        }

        $this->class = $class;
        $this->username = $username;
        $this->series = $series;
        $this->tokenValue = $tokenValue;
        $this->lastUsed = $lastUsed;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function getSeries(): string
    {
        return $this->series;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenValue(): string
    {
        return $this->tokenValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastUsed(): \DateTime
    {
        return $this->lastUsed;
    }
}
