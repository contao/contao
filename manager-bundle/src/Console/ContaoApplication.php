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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

/**
 * @method ContaoKernel getKernel()
 */
class ContaoApplication extends Application
{
    public function __construct(ContaoKernel $kernel)
    {
        parent::__construct($kernel);

        $this->setName('Contao Managed Edition');
        $this->setVersion(ContaoCoreBundle::getVersion());

        $inputDefinition = $this->getDefinition();
        $options = $inputDefinition->getOptions();

        // Contao does not support the no-debug option, so unset it
        unset($options['no-debug']);

        $inputDefinition->setOptions($options);
    }
}
