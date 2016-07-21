<?php

namespace Contao\ManagerBundle;

use Contao\ManagerBundle\Autoload\AutoloadPluginInterface;
use Contao\ManagerBundle\Autoload\IniParser;
use Contao\ManagerBundle\Autoload\JsonParser;

class ContaoManagerPlugin implements AutoloadPluginInterface
{
    /**
     * @inheritdoc
     */
    public function getAutoloadConfigs(JsonParser $jsonParser, IniParser $iniParser)
    {
        return $jsonParser->parse(__DIR__ . '/Resources/contao-manager/autoload.json');
    }
}
