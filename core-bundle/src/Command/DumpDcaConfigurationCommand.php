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

use Contao\CoreBundle\Dca\DcaConfiguration;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'config:dump-dca',
    description: 'Dump the default DCA configuration.',
)]
class DumpDcaConfigurationCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to a node in the DCA')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $configuration = new DcaConfiguration('default');

        $path = $input->getArgument('path');
        $message = 'Contao DCA configuration';

        if (null !== $path) {
            $message .= sprintf(' at path "%s"', $path);
        }

        $io->writeln(sprintf('# %s', $message));
        $dumper = new YamlReferenceDumper();

        $io->writeln(null === $path ? $dumper->dump($configuration) : $dumper->dumpAtPath($configuration, $path));

        return Command::SUCCESS;
    }
}
