<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ImportUserEvent extends Event
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $table;

    /**
     * @var bool
     */
    private $isLoaded = false;

    public function __construct(string $username, string $password, string $table)
    {
        $this->username = $username;
        $this->password = $password;
        $this->table = $table;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function isLoaded(): bool
    {
        return $this->isLoaded;
    }

    public function setIsLoaded(bool $isLoaded): self
    {
        $this->isLoaded = $isLoaded;

        return $this;
    }
}
