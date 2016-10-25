<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\ContaoManager;

use Contao\ManagerBundle\ContaoManager\Bundle\BundleConfig;
use Contao\ManagerBundle\ContaoManager\Bundle\BundlePluginInterface;
use Contao\ManagerBundle\ContaoManager\Bundle\IniParser;
use Contao\ManagerBundle\ContaoManager\Bundle\JsonParser;

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
    public function getBundles(JsonParser $jsonParser, IniParser $iniParser)
    {
        return [
            BundleConfig::create('Contao\CalendarBundle\ContaoCalendarBundle')
                ->setLoadAfter(['Contao\CoreBundle\ContaoCoreBundle'])
                ->setReplace(['calendar'])
        ];
    }
}
