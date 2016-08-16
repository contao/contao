<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle;

use Contao\ManagerBundle\Manager\Bundle\BundlePluginInterface;
use Contao\ManagerBundle\Manager\Bundle\IniParser;
use Contao\ManagerBundle\Manager\Bundle\JsonParser;

/**
 * Plugin for the Contao Manager.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoManagerPlugin implements BundlePluginInterface
{
    /**
     * @inheritdoc
     */
    public function getAutoloadConfigs(JsonParser $jsonParser, IniParser $iniParser)
    {
        return $jsonParser->parse(__DIR__ . '/Resources/contao-manager/bundles.json');
    }
}
