<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Picker;

use Knp\Menu\ItemInterface;

interface PickerProviderInterface
{
    /**
     * Returns the unique name for this picker.
     */
    public function getName(): string;

    /**
     * Returns the URL to the picker based on the current value.
     */
    public function getUrl(PickerConfig $config): string|null;

    /**
     * Creates the menu item for this picker.
     */
    public function createMenuItem(PickerConfig $config): ItemInterface;

    /**
     * Returns whether the picker supports the given context.
     */
    public function supportsContext(string $context): bool;

    /**
     * Returns whether the picker supports the given value.
     */
    public function supportsValue(PickerConfig $config): bool;

    /**
     * Returns whether the picker is currently active.
     */
    public function isCurrent(PickerConfig $config): bool;
}
