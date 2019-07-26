<?php

namespace Contao\ManagerBundle\Console;

use Contao\CoreBundle\Util\PackageUtil;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

class ContaoApplication extends Application
{
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct($kernel);

        $this->setName('Contao Managed Edition');

        try {
            $this->setVersion(PackageUtil::getVersion('contao/core-bundle'));
        } catch (\OutOfBoundsException $e) {
            $this->setVersion(PackageUtil::getVersion('contao/contao'));
        }

        $inputDefinition = $this->getDefinition();
        $options = $inputDefinition->getOptions();

        foreach ($options as $k => $option) {
            if ('no-debug' === $option->getName()) {
                // We do not support the no-debug option, so unset it
                unset($options[$k]);
                break;
            }
        }

        $inputDefinition->setOptions($options);
    }
}
