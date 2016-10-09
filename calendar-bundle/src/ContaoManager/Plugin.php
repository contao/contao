<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\ContaoManager;

use Contao\ManagerBundle\Manager\Bundle\BundleConfig;
use Contao\ManagerBundle\Manager\Bundle\BundlePluginInterface;
use Contao\ManagerBundle\Manager\Bundle\IniParser;
use Contao\ManagerBundle\Manager\Bundle\JsonParser;

/**
 * Plugin for the Contao Manager.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class Plugin implements BundlePluginInterface
{
    /**
     * @inheritdoc
     */
    public function getAutoloadConfigs(JsonParser $jsonParser, IniParser $iniParser)
    {
        return [
            BundleConfig::create('Contao\CalendarBundle\ContaoCalendarBundle')
                ->setLoadAfter(['Contao\CoreBundle\ContaoCoreBundle'])
                ->setReplace(['calendar'])
        ];
    }
}
