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
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoaderWarmer;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;

/**
 * @experimental
 */
class DebugContaoTwigCommand extends Command
{
    protected static $defaultName = 'debug:contao-twig';
    protected static $defaultDescription = 'Displays the Contao template hierarchy.';

    public function __construct(
        private TemplateHierarchyInterface $hierarchy,
        private ContaoFilesystemLoaderWarmer $cacheWarmer,
        private ThemeNamespace $themeNamespace,
        private string $projectDir,
        private Inspector $inspector,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('theme', 't', InputOption::VALUE_OPTIONAL, 'Include theme templates with a given theme path or slug.')
            ->addArgument('filter', InputArgument::OPTIONAL, 'Filter the output by an identifier or prefix.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Make sure the template hierarchy is up-to-date
        $this->cacheWarmer->refresh();

        $chains = $this->hierarchy->getInheritanceChains($this->getThemeSlug($input));

        if (null !== ($prefix = $input->getArgument('filter'))) {
            $chains = array_filter(
                $chains,
                static fn (string $identifier) => str_starts_with($identifier, $prefix),
                ARRAY_FILTER_USE_KEY
            );
        }

        $io = new SymfonyStyle($input, $output);
        $nameCellStyle = new TableCellStyle(['fg' => 'yellow']);
        $blockCellStyle = new TableCellStyle(['fg' => 'magenta']);
        $codeCellStyle = new TableCellStyle(['fg' => 'white']);

        foreach ($chains as $identifier => $chain) {
            $io->title($identifier);

            $rows = [];

            foreach ($chain as $path => $name) {
                $templateInformation = $this->inspector->inspectTemplate($name);

                $rows = [
                    ...$rows,
                    ['Original name', new TableCell($name, ['style' => $nameCellStyle])],
                    ['@Contao name', new TableCell("@Contao/$identifier.html.twig", ['style' => $nameCellStyle])],
                    ['Path', $path],
                    ['', ''],
                ];

                if ($blocks = $templateInformation->getBlocks()) {
                    $rows = [
                        ...$rows,
                        ...$this->formatMultiline(
                            'Blocks',
                            wordwrap(implode(', ', $blocks)),
                            $blockCellStyle
                        ),
                        ['', ''],
                    ];
                }

                if (!str_ends_with($name, '.html5')) {
                    $rows = [
                        ...$rows,
                        ...$this->formatMultiline(
                            'Preview',
                            $this->createPreview($templateInformation->getCode()),
                            $codeCellStyle
                        ),
                        ['', ''],
                    ];
                }

                $rows[] = new TableSeparator();
            }

            array_pop($rows);

            $io->table(['Attribute', 'Value'], $rows);
        }

        return Command::SUCCESS;
    }

    private function getThemeSlug(InputInterface $input): string|null
    {
        if (null === ($pathOrSlug = $input->getOption('theme'))) {
            return null;
        }

        if (is_dir(Path::join($this->projectDir, 'templates', $pathOrSlug))) {
            return $this->themeNamespace->generateSlug($pathOrSlug);
        }

        return $pathOrSlug;
    }

    private function createPreview(string $code): string
    {
        $limitToChars = 300;

        $shortened = mb_strlen($code) > $limitToChars ?
            substr($code, 0, $limitToChars).'…' :
            $code
        ;

        return trim($shortened);
    }

    private function formatMultiline(string $attribute, string $multilineValue, TableCellStyle $style): array
    {
        $lines = explode("\n", $multilineValue);
        $rows = [];

        foreach ($lines as $i => $line) {
            $rows[] = [0 === $i ? $attribute : '', new TableCell($line, ['style' => $style])];
        }

        return $rows;
    }
}
