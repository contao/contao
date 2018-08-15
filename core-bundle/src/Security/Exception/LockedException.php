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

    /**
     * @param int             $lockedSeconds
     * @param string          $message
     * @param int             $code
     * @param \Throwable|null $previous
     */
    public function __construct(int $lockedSeconds, string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->lockedSeconds = $lockedSeconds;
    }

    /**
     * Gets the number of seconds of locking.
     *
     * @return int
     */
    public function getLockedSeconds(): int
    {
        return $this->lockedSeconds;
    }

    /**
     * Gets the number of minutes of locking.
     *
     * @return int
     */
    public function getLockedMinutes(): int
    {
        return (int) ceil($this->lockedSeconds / 60);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([$this->lockedSeconds, parent::serialize()]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str): void
    {
        [$this->lockedSeconds, $parentData] = unserialize($str, ['allowed_classes' => true]);

        parent::unserialize($parentData);
    }
}
