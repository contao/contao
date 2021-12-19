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

use Contao\DataContainer;

class FilterPageTypeEvent
{
    private array $options;
    private DataContainer $dataContainer;

    public function __construct(array $options, DataContainer $dataContainer)
    {
        $this->options = $options;
        $this->dataContainer = $dataContainer;
    }

    public function getDataContainer(): DataContainer
    {
        return $this->dataContainer;
    }

    public function getOptions(): array
    {
        return array_values($this->options);
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function addOption(string $option): self
    {
        if (!\in_array($option, $this->options, true)) {
            $this->options[] = $option;
        }

        return $this;
    }

    public function removeOption(string $option): self
    {
        $key = array_search($option, $this->options, true);

        if (false !== $key) {
            unset($this->options[$key]);
        }

        return $this;
    }
}
