<?php

namespace Contao\ManagerBundle\Console;

use PackageVersions\Versions;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

class ContaoApplication extends Application
{
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct($kernel);

        $this->setName('Contao Managed Edition');
        $this->setVersion(Versions::VERSIONS['contao/contao'] ?? Versions::getVersion('contao/core-bundle'));

        $inputDefinition = $this->getDefinition();
        $options = $inputDefinition->getOptions();

        foreach ($options as $k => $option) {
            if ('no-debug' === $option->getName()) {
                unset($options[$k]);
                break;
            }
        }

        $inputDefinition->setOptions($options);
    }
}
