<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Menu;

use Symfony\Component\HttpFoundation\Request;

/**
 * Creates the picker menu.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
interface PickerMenuBuilderInterface
{
    /**
     * Creates the menu.
     *
     * @param string $context
     *
     * @return string
     */
    public function createMenu($context);

    /**
     * Checks if a table is supported.
     *
     * @param string $table
     *
     * @return bool
     */
    public function supportsTable($table);

    /**
     * Processes the selected value.
     *
     * @param $table
     * @param $value
     *
     * @return string
     */
    public function processSelection($table, $value);

    /**
     * Returns the picker URL.
     *
     * @param Request $request
     *
     * @return string
     */
    public function getPickerUrl(Request $request);
}
