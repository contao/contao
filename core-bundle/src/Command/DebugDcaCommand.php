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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

/**
 * Dumps debug information about a Contao DCA.
 */
class DebugDcaCommand extends Command
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('debug:dca')
            ->addArgument('table', InputArgument::REQUIRED, 'The table name')
            ->setDescription('Dumps the DCA configuration for a table.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = $input->getArgument('table');

        $dcaLoader = $this->framework->createInstance(DcaLoader::class, [$table]);
        $dcaLoader->load();

        $cloner = new VarCloner();
        $dumper = new CliDumper();

        $dumper->dump($cloner->cloneVar($GLOBALS['TL_DCA'][$table]));

        return 0;
    }
}
