<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Manager\Bundle;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
interface BundlePluginInterface
{
    /**
     * Gets a list of autoload configurations for this bundle.
     *
     * @param JsonParser $jsonParser
     * @param IniParser  $iniParser
     *
     * @return ConfigInterface[]
     */
    public function getAutoloadConfigs(JsonParser $jsonParser, IniParser $iniParser);
}
