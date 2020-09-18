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

class UndoDescriptionEvent extends Event
{
    /**
     * @var string
     */
    private $table;

    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $descriptor;

    /**
     * @var array
     */
    private $options;

    public function __construct(string $table, array $data, array $options = [])
    {
        $this->table = $table;
        $this->data = $data;
        $this->options = $options;
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getDescriptor(): ?string
    {
        return $this->descriptor;
    }

    /**
     * @param ?string $descriptor
     */
    public function setDescriptor(string $descriptor = null): void
    {
        $this->descriptor = $descriptor;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
