<?php
/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Fixtures\Command;

use Contao\CoreBundle\Command\ContaoFrameworkDependentInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Class FrameworkDependentCommand
 *
 * @author Dominik Tomasi <https://github.com/dtomasi>
 */
class FrameworkDependentCommand extends Command implements ContaoFrameworkDependentInterface
{

    protected function configure()
    {
        $this
            ->setName('fixture:frameworkdependent')
            ->setDescription('Greet someone');
    }
}
