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

/**
 * @deprecated Deprecated since Contao 5.3, to be removed in Contao 6. Use DCA permissions instead.
 */
class FilterPageTypeEvent
{
    public function __construct(
        private array $options,
        private readonly DataContainer $dataContainer,
    ) {
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
        trigger_deprecation('contao/core-bundle', '5.3', 'The FilterPageTypeEvent is deprecated, use DCA permissions instead.');

        $this->options = $options;

        return $this;
    }

    public function addOption(string $option): self
    {
        trigger_deprecation('contao/core-bundle', '5.3', 'The FilterPageTypeEvent is deprecated, use DCA permissions instead.');

        if (!\in_array($option, $this->options, true)) {
            $this->options[] = $option;
        }

        return $this;
    }

    public function removeOption(string $option): self
    {
        trigger_deprecation('contao/core-bundle', '5.3', 'The FilterPageTypeEvent is deprecated, use DCA permissions instead.');

        $key = array_search($option, $this->options, true);

        if (false !== $key) {
            unset($this->options[$key]);
        }

        return $this;
    }
}
