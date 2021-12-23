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
     *
     * @return PickerConfig
     */
    public function getConfig()/*: PickerConfig*/;

    /**
     * Returns the picker menu.
     *
     * @return ItemInterface
     */
    public function getMenu()/*: ItemInterface*/;

    /**
     * Returns the current provider.
     *
     * @return PickerProviderInterface|null
     */
    public function getCurrentProvider()/*: ?PickerProviderInterface*/;

    /**
     * Returns the URL to the current picker tab.
     *
     * @return string
     */
    public function getCurrentUrl()/*: string*/;
}
