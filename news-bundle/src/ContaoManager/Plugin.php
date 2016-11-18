<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\ContaoManager;

use Contao\ManagerBundle\ContaoManager\Bundle\BundleConfig;
use Contao\ManagerBundle\ContaoManager\Bundle\BundlePluginInterface;
use Contao\ManagerBundle\ContaoManager\Bundle\ParserInterface;

/**
 * Plugin for the Contao Manager.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create('Contao\NewsBundle\ContaoNewsBundle')
                ->setLoadAfter(['Contao\CoreBundle\ContaoCoreBundle'])
                ->setReplace(['news']),
        ];
    }
}
