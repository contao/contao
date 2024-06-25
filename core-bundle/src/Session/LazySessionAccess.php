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

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Automatically starts the session if someone accesses $_SESSION.
 *
 * @internal
 * @implements \ArrayAccess<mixed, mixed>
 */
class LazySessionAccess implements \ArrayAccess, \Countable
{
    private SessionInterface $session;
    private bool $hasPreviousSession;

    public function __construct(SessionInterface $session, bool $hasPreviousSession = true)
    {
        $this->session = $session;
        $this->hasPreviousSession = $hasPreviousSession;
    }

    /**
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        if (!$this->hasPreviousSession && !$this->session->isStarted()) {
            return false;
        }

        $this->startSession();

        return \array_key_exists($offset, $_SESSION);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function &offsetGet($offset)
    {
        $this->startSession();

        return $_SESSION[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->startSession();

        $_SESSION[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        $this->startSession();

        unset($_SESSION[$offset]);
    }

    public function count(): int
    {
        if (!$this->hasPreviousSession && !$this->session->isStarted()) {
            return 0;
        }

        $this->startSession();

        return \count($_SESSION);
    }

    private function startSession(): void
    {
        trigger_deprecation('contao/core-bundle', '4.5', 'Using "$_SESSION" has been deprecated and will no longer work in Contao 5.0. Use the Symfony session instead.');

        $this->session->start();

        /** @phpstan-ignore instanceof.alwaysFalse */
        if ($_SESSION instanceof self) {
            throw new \RuntimeException('Unable to start the native session, $_SESSION was not replaced.');
        }

        // Accessing the session object may replace the global $_SESSION variable,
        // so we store the bags in a local variable first before setting them on $_SESSION
        $beBag = $this->session->getBag('contao_backend');
        $feBag = $this->session->getBag('contao_frontend');

        $_SESSION['BE_DATA'] = $beBag;
        $_SESSION['FE_DATA'] = $feBag;
    }
}
