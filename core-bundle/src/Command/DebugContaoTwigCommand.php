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
use Contao\CoreBundle\Twig\Loader\Theme;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webmozart\PathUtil\Path;

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

    /**
     * @var Theme
     */
    private $theme;

    /**
     * @var string
     */
    private $projectDir;

    public function __construct(TemplateHierarchyInterface $hierarchy, ContaoFilesystemLoaderWarmer $cacheWarmer, Theme $theme, string $projectDir)
    {
        $this->hierarchy = $hierarchy;
        $this->cacheWarmer = $cacheWarmer;
        $this->theme = $theme;
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Displays the Contao template hierarchy.')
            ->addOption('theme', 't', InputOption::VALUE_OPTIONAL, 'Include theme templates with a given theme path or slug.')
            ->addArgument('filter', InputArgument::OPTIONAL, 'Filter the output by an identifier or prefix.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Make sure the template hierarchy is up-to-date
        $this->cacheWarmer->refresh();

        $rows = [];
        $chains = $this->hierarchy->getInheritanceChains($this->getThemeSlug($input));

        if (null !== ($prefix = $input->getArgument('filter'))) {
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

        return 0;
    }

    private function getThemeSlug(InputInterface $input): ?string
    {
        if (null === ($pathOrSlug = $input->getOption('theme'))) {
            return null;
        }

        if (is_dir(Path::join($this->projectDir, 'templates', $pathOrSlug))) {
            return $this->theme->generateSlug($pathOrSlug);
        }

        return $pathOrSlug;
    }
}
