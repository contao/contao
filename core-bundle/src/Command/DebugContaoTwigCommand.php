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

use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Symfony\Component\Console\Attribute\AsCommand;
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

#[AsCommand(
    name: 'debug:contao-twig',
    description: 'Displays the Contao template hierarchy.',
)]
class DebugContaoTwigCommand extends Command
{
    public function __construct(
        private readonly ContaoFilesystemLoader $filesystemLoader,
        private readonly ThemeNamespace $themeNamespace,
        private readonly string $projectDir,
        private readonly Inspector $inspector,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('theme', 't', InputOption::VALUE_OPTIONAL, 'Include theme templates with a given theme path or slug.')
            ->addOption('tree', null, InputOption::VALUE_NONE, 'Display the templates as prefix tree.')
            ->addArgument('filter', InputArgument::OPTIONAL, 'Filter the output by an identifier or prefix.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Make sure the template hierarchy is up-to-date
        $this->filesystemLoader->warmUp(true);

        $chains = $this->filesystemLoader->getInheritanceChains($this->getThemeSlug($input));

        if (null !== ($prefix = $input->getArgument('filter'))) {
            $chains = array_filter(
                $chains,
                static fn (string $identifier) => str_starts_with($identifier, $prefix),
                ARRAY_FILTER_USE_KEY,
            );
        }

        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('tree')) {
            $this->listTree($chains, $io);
        } else {
            $this->listDetailed($chains, $io);
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<string, array<string, string>> $chains
     */
    private function listTree(array $chains, SymfonyStyle $io): void
    {
        // Split identifier prefixes by "/" and arrange them in a prefix tree
        $prefixTree = [];

        foreach ($chains as $identifier => $chain) {
            $parts = explode('/', $identifier);
            $node = &$prefixTree;

            foreach ($parts as $part) {
                /** @phpstan-ignore isset.offset */
                if (!isset($node[$part])) {
                    $node[$part] = [];
                }

                $node = &$node[$part];
            }

            $node = [...$node, ...$chain];
        }

        // Recursively display tree nodes
        $displayNode = static function (array $node, string $prefix = '', string $namePrefix = '') use ($io, $chains, &$displayNode): void {
            // Make sure leaf nodes (files) come first and everything else is sorted
            // ascending by its key (identifier part)
            uksort(
                $node,
                static function ($keyA, $keyB) use ($node) {
                    if (0 !== ($leafNodes = (\is_array($node[$keyA]) <=> \is_array($node[$keyB])))) {
                        return $leafNodes;
                    }

                    return $keyA <=> $keyB;
                },
            );

            $count = \count($node);

            foreach ($node as $label => $element) {
                --$count;

                $currentPrefix = $prefix.($count ? '├──' : '└──');
                $currentPrefixWithNewline = $prefix.($count ? '│  ' : '   ');

                if (\is_array($element)) {
                    // Display part of the template identifier. If this is the last bit, we also
                    // display the effective @Contao name.
                    $identifier = ltrim("$namePrefix/$label", '/');

                    $io->writeln(\sprintf(
                        '%s<fg=green;options=bold>%s</>%s',
                        $currentPrefix,
                        $label,
                        isset($chains[$identifier]) ? " (<fg=yellow>@Contao/$identifier.html.twig</>)" : '',
                    ));

                    $displayNode($element, $currentPrefixWithNewline, $identifier);

                    continue;
                }

                // Display file and logical name
                $io->writeln($currentPrefix.$label);

                $io->writeln(\sprintf(
                    '%s<fg=white>Original name:</> <fg=yellow>%s</>',
                    $currentPrefixWithNewline,
                    $element,
                ));
            }
        };

        $displayNode($prefixTree);
    }

    /**
     * @param array<string, array<string, string>> $chains
     */
    private function listDetailed(array $chains, SymfonyStyle $io): void
    {
        $nameCellStyle = new TableCellStyle(['fg' => 'yellow']);
        $blockCellStyle = new TableCellStyle(['fg' => 'magenta']);
        $slotCellStyle = new TableCellStyle(['fg' => 'blue']);
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
                            $blockCellStyle,
                        ),
                        ['', ''],
                    ];
                }

                if ($slots = $templateInformation->getSlots()) {
                    $rows = [
                        ...$rows,
                        ...$this->formatMultiline(
                            'Slots',
                            wordwrap(implode(', ', $slots)),
                            $slotCellStyle,
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
                            $codeCellStyle,
                        ),
                        ['', ''],
                    ];
                }

                $rows[] = new TableSeparator();
            }

            array_pop($rows);

            $io->table(['Attribute', 'Value'], $rows);
        }
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

        $shortened = mb_strlen($code) > $limitToChars
            ? substr($code, 0, $limitToChars).'…'
            : $code;

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
