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
    description: 'Dumps the DCA configuration for a table.',
)]
class DebugDcaCommand extends Command
{
    public function __construct(private readonly ContaoFramework $framework)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('table', InputArgument::REQUIRED, 'The table name');
        $this->addArgument('path', InputArgument::OPTIONAL, 'Dot-notation for a portion of the DCA to dump');
        $this->addUsage('tl_member fields.username');
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

        $keys = array_filter(explode('.', (string) $input->getArgument('path')));
        $dcaRef = &$this->getDcaReference($table, $keys);

        $dumper->dump($cloner->cloneVar($dcaRef));

        return Command::SUCCESS;
    }

    private function &getDcaReference(string $table, array $keys): array|callable|null
    {
        $dcaRef = &$GLOBALS['TL_DCA'][$table];

        foreach ($keys as $key) {
            $dcaRef = &$dcaRef[$key];
        }

        return $dcaRef;
    }
}
