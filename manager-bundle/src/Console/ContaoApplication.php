<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Console;

use Contao\CoreBundle\Util\PackageUtil;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\HttpKernel\KernelInterface;

class ContaoApplication extends Application
{
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct($kernel);

        $this->setName('Contao Managed Edition');
        $this->setVersion(PackageUtil::getContaoVersion());

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

    public static function createConsoleInput()
    {
        $argv = $_SERVER['argv'];

        // Ignore the --no-debug option
        foreach ($argv as $k => $v) {
            if ($v === '--no-debug') {
                unset($argv[$k]);
            }
        }

        return new ArgvInput(array_values($argv));
    }
}
