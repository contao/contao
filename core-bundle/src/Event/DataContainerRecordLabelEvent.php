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

class DataContainerRecordLabelEvent extends Event
{
    private string|null $label = null;

    public function __construct(
        private readonly string $identifier,
        private readonly array $data,
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getLabel(): string|null
    {
        return $this->label;
    }

    public function setLabel(string|null $label): self
    {
        $this->label = $label;

        return $this;
    }
}
