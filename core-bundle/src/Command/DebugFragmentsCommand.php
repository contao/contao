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

use Contao\CoreBundle\Fragment\FragmentConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'debug:fragments',
    description: 'Displays the fragment controller configuration.',
)]
class DebugFragmentsCommand extends Command
{
    private array $identifiers = [];
    private array $attributes = [];

    /**
     * @var array<FragmentConfig>
     */
    private array $configs = [];

    public function add(string $identifier, FragmentConfig $config, array $attributes): void
    {
        $this->identifiers[] = $identifier;
        $this->configs[$identifier] = $config;
        $this->attributes[$identifier] = $attributes;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rows = [];
        $identifiers = $this->identifiers;
        natsort($identifiers);

        foreach ($identifiers as $identifier) {
            $attributes = $this->attributes[$identifier];
            $controller = $attributes['debugController'] ?? $this->configs[$identifier]->getController();
            unset($attributes['debugController']);

            $rows[] = [
                $identifier,
                $controller,
                $this->configs[$identifier]->getRenderer(),
                $this->generateArray($this->configs[$identifier]->getOptions()),
                $this->generateArray($attributes),
            ];
        }

        $io = new SymfonyStyle($input, $output);
        $io->title('Contao Fragments');
        $io->table(['Identifier', 'Controller', 'Renderer', 'Render Options', 'Fragment Options'], $rows);

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
            0,
        );

        $return = [];

        foreach ($values as $k => $v) {
            if (\is_bool($v)) {
                $v = $v ? 'true' : 'false';
            }

            $return[] = sprintf('%s : %s', str_pad($k, $length, ' ', STR_PAD_RIGHT), $v);
        }

        return implode("\n", $return);
    }
}
