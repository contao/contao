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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DebugFragmentsCommand extends Command
{
    protected static $defaultName = 'debug:fragments';

    /**
     * @var array
     */
    private $identifiers = [];

    /**
     * @var array<FragmentConfig>
     */
    private $configs = [];

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var SymfonyStyle
     */
    private $io;

    public function add(string $identifier, FragmentConfig $config, array $attributes): void
    {
        $this->identifiers[] = $identifier;
        $this->configs[$identifier] = $config;
        $this->attributes[$identifier] = $attributes;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Displays the fragment controller configuration.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $rows = [];
        $identifiers = $this->identifiers;
        natsort($identifiers);

        foreach ($identifiers as $identifier) {
            $rows[] = [
                $identifier,
                $this->configs[$identifier]->getController(),
                $this->configs[$identifier]->getRenderer(),
                $this->generateArray($this->configs[$identifier]->getOptions()),
                $this->generateArray($this->attributes[$identifier]),
            ];
        }

        $this->io->title('Contao Fragments');
        $this->io->table(['Identifier', 'Controller', 'Renderer', 'Render Options', 'Fragment Options'], $rows);

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

        return implode("\n", $return);
    }
}
