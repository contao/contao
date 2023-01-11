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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DcaLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

#[AsCommand(
    name: 'debug:dca',
    description: 'Dumps the DCA configuration for a table.'
)]
class DebugDcaCommand extends Command
{
    public function __construct(private ContaoFramework $framework)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('table', InputArgument::REQUIRED, 'The table name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = $input->getArgument('table');

        $this->framework->initialize();
        $dcaLoader = $this->framework->createInstance(DcaLoader::class, [$table]);
        $dcaLoader->load();

        if (!isset($GLOBALS['TL_DCA'][$table])) {
            throw new InvalidArgumentException('Invalid table name: '.$table);
        }

        $cloner = new VarCloner();
        $dumper = new CliDumper();

        $dumper->dump($cloner->cloneVar($GLOBALS['TL_DCA'][$table]));

        return Command::SUCCESS;
    }
}
