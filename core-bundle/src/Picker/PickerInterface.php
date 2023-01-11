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

interface PickerInterface
{
    /**
     * Returns the picker configuration.
     */
    public function getConfig(): PickerConfig;

    /**
     * Returns the picker menu.
     */
    public function getMenu(): ItemInterface;

    /**
     * Returns the current provider.
     */
    public function getCurrentProvider(): PickerProviderInterface|null;

    /**
     * Returns the URL to the current picker tab.
     */
    public function getCurrentUrl(): string|null;
}
