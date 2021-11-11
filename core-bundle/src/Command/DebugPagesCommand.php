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
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\RouteConfig;
use Contao\PageModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DebugPagesCommand extends Command
{
    protected static $defaultName = 'debug:pages';

    private ContaoFramework $framework;
    private PageRegistry $pageRegistry;

    /**
     * @var array<RouteConfig>
     */
    private array $routeConfigs = [];

    /**
     * @var array<DynamicRouteInterface>
     */
    private array $routeEnhancers = [];

    public function __construct(ContaoFramework $framework, PageRegistry $pageRegistry)
    {
        parent::__construct();

        $this->framework = $framework;
        $this->pageRegistry = $pageRegistry;
    }

    public function add(string $type, RouteConfig $config, DynamicRouteInterface $routeEnhancer = null): void
    {
        $this->routeConfigs[$type] = $config;

        if (null !== $routeEnhancer) {
            $this->routeEnhancers[$type] = $routeEnhancer;
        }
    }

    protected function configure(): void
    {
        $this->setDescription('Displays the page controller configuration.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->framework->initialize();

        $io = new SymfonyStyle($input, $output);

        $rows = [];
        $types = array_unique(array_merge(array_keys($GLOBALS['TL_PTY']), $this->pageRegistry->keys()));
        natsort($types);

        foreach ($types as $type) {
            $config = $this->routeConfigs[$type] ?? null;

            $page = new PageModel();
            $page->type = $type;

            $rows[] = [
                $type,
                $config && $config->getPath() ? $config->getPath() : '*',
                $config && $config->getUrlSuffix() ? $config->getUrlSuffix() : '*',
                $this->pageRegistry->supportsContentComposition($page) ? 'yes' : 'no',
                isset($this->routeEnhancers[$type]) ? \get_class($this->routeEnhancers[$type]) : '-',
                $config ? $this->generateArray($config->getRequirements()) : '-',
                $config ? $this->generateArray($config->getDefaults()) : '-',
                $config ? $this->generateArray($config->getOptions()) : '-',
            ];
        }

        $io->title('Contao Pages');
        $io->table(['Type', 'Path', 'URL Suffix', 'Content Composition', 'Route Enhancer', 'Requirements', 'Defaults', 'Options'], $rows);

        return 0;
    }

    private function generateArray(array $values): string
    {
        $length = array_reduce(
            array_keys($values),
            static function ($carry, $item): int {
                $length = \strlen($item);

                return $carry > $length ? $carry : $length;
            },
            0
        );

        $return = [];

        foreach ($values as $k => $v) {
            if (\is_bool($v)) {
                $v = $v ? 'true' : 'false';
            }

            $return[] = sprintf('%s : %s', str_pad($k, $length, ' ', STR_PAD_RIGHT), (string) $v);
        }

        return !empty($return) ? implode("\n", $return) : '-';
    }
}
