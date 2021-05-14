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

use Contao\CoreBundle\Twig\Inheritance\HierarchyProvider;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoaderWarmer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DebugContaoTwigCommand extends Command
{
    protected static $defaultName = 'debug:contao-twig';

    /**
     * @var HierarchyProvider
     */
    private $hierarchyProvider;

    /**
     * @var ContaoFilesystemLoaderWarmer
     */
    private $cacheWarmer;

    public function __construct(HierarchyProvider $hierarchyProvider, ContaoFilesystemLoaderWarmer $cacheWarmer)
    {
        $this->hierarchyProvider = $hierarchyProvider;
        $this->cacheWarmer = $cacheWarmer;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Displays the template hierarchy.')
            ->addOption('refresh', 'r', InputOption::VALUE_NONE, 'Refresh the cache.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->hasOption('refresh')) {
            $this->cacheWarmer->refresh();

            $io->success('Template loader cache and hierarchy was successfully rebuild.');
        }

        $rows = [];
        $hierarchy = $this->hierarchyProvider->getHierarchy();

        foreach ($hierarchy as $identifier => $templates) {
            $i = 0;

            foreach ($templates as $path => $namespace) {
                $rows[] = [
                    0 === $i ? $identifier : '',
                    $namespace,
                    $path,
                ];
                ++$i;
            }

            $rows[] = new TableSeparator();
        }

        array_pop($rows);

        $io->title('Template hierarchy');
        $io->table(['Identifier', 'Effective namespace', 'Path'], $rows);

        return 0;
    }
}
