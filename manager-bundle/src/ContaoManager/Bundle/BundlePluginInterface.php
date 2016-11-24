<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\ContaoManager\Bundle;

use Contao\ManagerBundle\ContaoManager\Bundle\Config\ConfigInterface;
use Contao\ManagerBundle\ContaoManager\Bundle\Parser\ParserInterface;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
interface BundlePluginInterface
{
    /**
     * Gets a list of autoload configurations for this bundle.
     *
     * @param ParserInterface $parser
     *
     * @return ConfigInterface[]
     */
    public function getBundles(ParserInterface $parser);
}
