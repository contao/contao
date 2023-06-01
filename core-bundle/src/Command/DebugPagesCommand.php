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
use Contao\CoreBundle\Routing\Page\ContentCompositionInterface;
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\RouteConfig;
use Contao\PageModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'debug:pages',
    description: 'Displays the page controller configuration.'
)]
class DebugPagesCommand extends Command
{
    /**
     * @var array<RouteConfig>
     */
    private array $routeConfigs = [];

    /**
     * @var array<DynamicRouteInterface>
     */
    private array $routeEnhancers = [];

    /**
     * @var array<ContentCompositionInterface|bool>
     */
    private array $contentComposition = [];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly PageRegistry $pageRegistry,
    ) {
        parent::__construct();
    }

    public function add(string $type, RouteConfig $config, DynamicRouteInterface|null $routeEnhancer = null, ContentCompositionInterface|bool $contentComposition = true): void
    {
        $this->routeConfigs[$type] = $config;

        if (null !== $routeEnhancer) {
            $this->routeEnhancers[$type] = $routeEnhancer;
        }

        if (null !== $contentComposition) {
            $this->contentComposition[$type] = $contentComposition;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->framework->initialize();

        $io = new SymfonyStyle($input, $output);

        $rows = [];
        $types = array_unique([...array_keys($GLOBALS['TL_PTY']), ...$this->pageRegistry->keys()]);
        natsort($types);

        foreach ($types as $type) {
            $config = $this->routeConfigs[$type] ?? null;

            $page = new PageModel();
            $page->type = $type;

            $contentComposition = $this->pageRegistry->supportsContentComposition($page) ? 'yes' : 'no';

            if (($this->contentComposition[$type] ?? null) instanceof ContentCompositionInterface) {
                $contentComposition = 'dynamic';
            }

            $rows[] = [
                $type,
                $config?->getPath() ? $config->getPath() : '*',
                $config?->getUrlSuffix() ? $config->getUrlSuffix() : '*',
                $contentComposition,
                isset($this->routeEnhancers[$type]) ? $this->routeEnhancers[$type]::class : '-',
                $config ? $this->generateArray($config->getRequirements()) : '-',
                $config ? $this->generateArray($config->getDefaults()) : '-',
                $config ? $this->generateArray($config->getOptions()) : '-',
            ];
        }

        $io->title('Contao Pages');
        $io->table(['Type', 'Path', 'URL Suffix', 'Content Composition', 'Route Enhancer', 'Requirements', 'Defaults', 'Options'], $rows);

        return Command::SUCCESS;
    }

    private function generateArray(array $values): string
    {
        $length = array_reduce(
            array_keys($values),
            static function ($carry, $item): int {
                $length = \strlen((string) $item);

                return max($carry, $length);
            },
            0
        );

        $return = [];

        foreach ($values as $k => $v) {
            if (\is_bool($v)) {
                $v = $v ? 'true' : 'false';
            }

            $return[] = sprintf('%s : %s', str_pad($k, $length, ' ', STR_PAD_RIGHT), $v);
        }

        return !empty($return) ? implode("\n", $return) : '-';
    }
}
