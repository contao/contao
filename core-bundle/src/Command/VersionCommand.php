<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Command;

use Contao\CoreBundle\ContaoCoreBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 *
 * @deprecated Deprecated since Contao 4.4, to be removed in Contao 5.0; use
 *             "composer show | grep contao/core-bundle | awk '{ print $2 }'" instead
 */
class VersionCommand extends Command
{
    protected static $defaultName = 'contao:version';
    protected static $defaultDescription = 'Outputs the contao/core-bundle version (deprecated).';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(ContaoCoreBundle::getVersion());

        return 0;
    }
}
