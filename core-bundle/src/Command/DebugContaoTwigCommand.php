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

use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoaderWarmer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @experimental
 */
class DebugContaoTwigCommand extends Command
{
    protected static $defaultName = 'debug:contao-twig';

    /**
     * @var TemplateHierarchyInterface
     */
    private $hierarchy;

    /**
     * @var ContaoFilesystemLoaderWarmer
     */
    private $cacheWarmer;

    public function __construct(TemplateHierarchyInterface $hierarchy, ContaoFilesystemLoaderWarmer $cacheWarmer)
    {
        $this->hierarchy = $hierarchy;
        $this->cacheWarmer = $cacheWarmer;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Displays the Contao template hierarchy.')
            ->addOption('refresh', 'r', InputOption::VALUE_NONE, 'Refresh the cache.')
            ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL, 'Filter the output by an identifier or prefix.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rows = [];
        $chains = $this->hierarchy->getInheritanceChains();

        if (null !== ($prefix = $input->getOption('filter'))) {
            $chains = array_filter(
                $chains,
                static function (string $identifier) use ($prefix) {
                    return 0 === strpos($identifier, $prefix);
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        foreach ($chains as $identifier => $chain) {
            $i = 0;

            foreach ($chain as $path => $name) {
                $rows[] = [0 === $i ? $identifier : '', $name, $path];
                ++$i;
            }

            $rows[] = new TableSeparator();
        }

        array_pop($rows);

        $io = new SymfonyStyle($input, $output);
        $io->title('Template hierarchy');
        $io->table(['Identifier', 'Effective logical name', 'Path'], $rows);

        if ($input->getOption('refresh')) {
            $this->cacheWarmer->refresh();

            $io->success('Template loader cache and hierarchy was successfully rebuilt.');
        }

        return 0;
    }
}
