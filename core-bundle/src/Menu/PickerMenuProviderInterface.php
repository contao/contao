<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides data for the picker menu.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
interface PickerMenuProviderInterface
{
    /**
     * Checks if a context is supported.
     *
     * @param string $context
     *
     * @return bool
     */
    public function supports($context);

    /**
     * Creates the menu.
     *
     * @param ItemInterface    $menu
     * @param FactoryInterface $factory
     */
    public function createMenu(ItemInterface $menu, FactoryInterface $factory);

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
     * @param string $value
     *
     * @return string
     */
    public function processSelection($value);

    /**
     * Checks if a value can be handled.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function canHandle(Request $request);

    /**
     * Returns the picker URL.
     *
     * @param Request $request
     *
     * @return string
     */
    public function getPickerUrl(Request $request);
}
