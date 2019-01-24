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

use Contao\CoreBundle\Util\PackageUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated Deprecated since Contao 4.4, to be removed in Contao 5.0; use
 *             "composer show | grep contao/core-bundle | awk '{ print $2 }'" instead
 */
class VersionCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('contao:version')
            ->setDescription('Outputs the contao/core-bundle version (deprecated).')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->writeln(PackageUtil::getVersion('contao/core-bundle'));
        } catch (\OutOfBoundsException $e) {
            $output->writeln(PackageUtil::getVersion('contao/contao'));
        }

        return 0;
    }
}
