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
    private $description;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description = null): void
    {
        $this->description = $description;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
