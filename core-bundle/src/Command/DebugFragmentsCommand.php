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

use Contao\CoreBundle\Controller\AbstractFragmentController;
use Contao\CoreBundle\Fragment\FragmentRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[AsCommand(
    name: 'debug:fragments',
    description: 'Displays the fragment controller configuration.',
)]
class DebugFragmentsCommand extends Command
{
    public function __construct(
        private readonly FragmentRegistry $registry,
        private readonly ContainerInterface $container,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rows = [];
        $fragments = $this->registry->all();
        $identifiers = array_keys($fragments);
        natsort($identifiers);

        foreach ($identifiers as $identifier) {
            $config = $fragments[$identifier];
            $controller = $this->container->get($config->getController()) ?? $config->getController();
            $class = new \ReflectionClass(AbstractFragmentController::class);
            $attributes = $class->getProperty('options')->getValue($this->container->get($config->getController()));

            unset($attributes['debugController']);

            $rows[] = [
                $identifier,
                \is_string($controller) ? $controller : $controller::class,
                $config->getRenderer(),
                $this->generateArray($config->getOptions()),
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
