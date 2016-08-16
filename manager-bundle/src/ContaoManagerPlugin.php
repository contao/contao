<?php

namespace Contao\ManagerBundle;

use Contao\ManagerBundle\Manager\Bundle\BundlePluginInterface;
use Contao\ManagerBundle\Manager\Bundle\IniParser;
use Contao\ManagerBundle\Manager\Bundle\JsonParser;

class ContaoManagerPlugin implements BundlePluginInterface
{
    /**
     * @inheritdoc
     */
    public function getAutoloadConfigs(JsonParser $jsonParser, IniParser $iniParser)
    {
        return $jsonParser->parse(__DIR__ . '/Resources/contao-manager/autoload.json');
    }
}
