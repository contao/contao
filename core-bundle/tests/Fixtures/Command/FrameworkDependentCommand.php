<?php
/**
 * Created by PhpStorm.
 * User: d.tomasi
 * Date: 26.03.15
 * Time: 14:24
 */

namespace Contao\Fixtures\Command;

use Contao\CoreBundle\Command\FrameworkDependentCommandInterface;
use Symfony\Component\Console\Command\Command;

class FrameworkDependentCommand extends Command implements FrameworkDependentCommandInterface{

    protected function configure()
    {
        $this
            ->setName('fixture:frameworkdependent')
            ->setDescription('Greet someone')
        ;
    }
}