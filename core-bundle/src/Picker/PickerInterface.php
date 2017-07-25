<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Picker;

use Knp\Menu\ItemInterface;

/**
 * Picker interface.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
interface PickerInterface
{
    /**
     * Returns the picker configuration.
     *
     * @return PickerConfig
     */
    public function getConfig();

    /**
     * Returns the picker menu.
     *
     * @return ItemInterface
     */
    public function getMenu();

    /**
     * Returns the current provider.
     *
     * @return PickerProviderInterface|null
     */
    public function getCurrentProvider();

    /**
     * Returns the URL to the current picker tab.
     *
     * @return string
     */
    public function getCurrentUrl();
}
