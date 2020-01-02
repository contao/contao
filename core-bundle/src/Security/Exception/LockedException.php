<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Exception;

use Symfony\Component\Security\Core\Exception\LockedException as BaseLockedException;

class LockedException extends BaseLockedException
{
    /**
     * @var int
     */
    private $lockedSeconds;

    public function __construct(int $lockedSeconds, string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->lockedSeconds = $lockedSeconds;
    }

    /**
     * {@inheritdoc}
     */
    public function __serialize(): array
    {
        return [$this->lockedSeconds, parent::__serialize()];
    }

    /**
     * {@inheritdoc}
     */
    public function __unserialize(array $data): void
    {
        [$this->lockedSeconds, $parentData] = $data;

        parent::__unserialize($parentData);
    }

    public function getLockedSeconds(): int
    {
        return $this->lockedSeconds;
    }

    /**
     * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0; use
     *             LockedException::getLockedSeconds instead
     */
    public function getLockedMinutes(): int
    {
        @trigger_error('Using LockedException::getLockedMinutes() has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

        return (int) ceil($this->lockedSeconds / 60);
    }
}
