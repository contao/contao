<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

/**
 * @internal
 */
class MaintenanceModeCommand extends Command
{
    protected static $defaultName = 'contao:maintenance-mode';
    protected static $defaultDescription = 'Changes the state of the system maintenance mode.';

    private string $maintenanceFilePath;
    private Environment $twig;
    private Filesystem $filesystem;

    public function __construct(string $maintenanceFilePath, Environment $twig, Filesystem $filesystem = null)
    {
        $this->maintenanceFilePath = $maintenanceFilePath;
        $this->twig = $twig;
        $this->filesystem = $filesystem ?? new Filesystem();

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('state', InputArgument::OPTIONAL, 'Use "enable" to enable and "disable" to disable the maintenance mode. If the state is already the desired one, nothing happens. You can also use "on" and "off".')
            ->addOption('template', 't', InputOption::VALUE_REQUIRED, 'Allows to take a different Twig template name when enabling the maintenance mode.', '@ContaoCore/Error/service_unavailable.html.twig')
            ->addOption('templateVars', null, InputOption::VALUE_OPTIONAL, 'Add custom template variables to the Twig template when enabling the maintenance mode (provide as JSON).', '{}')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, json)', 'txt')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $state = $input->getArgument('state');

        if (\in_array($state, ['enable', 'on'], true)) {
            $this->enable($input->getOption('template'), $input->getOption('templateVars'));
            $this->outputResult($input, $output, true, true);

            return 0;
        }

        if (\in_array($state, ['disable', 'off'], true)) {
            $this->disable();
            $this->outputResult($input, $output, false, true);

            return 0;
        }

        $isEnabled = $this->filesystem->exists($this->maintenanceFilePath);

        $this->outputResult($input, $output, $isEnabled, false);

        return 0;
    }

    private function enable(string $templateName, string $templateVars): void
    {
        // Render the template and write it to maintenance.html
        $this->filesystem->dumpFile(
            $this->maintenanceFilePath,
            $this->twig->render($templateName, array_merge(
                [
                    'statusCode' => 503,
                    'language' => 'en',
                    'template' => $templateName,
                ],
                json_decode($templateVars, true)
            ))
        );
    }

    private function disable(): void
    {
        $this->filesystem->remove($this->maintenanceFilePath);
    }

    private function outputResult(InputInterface $input, OutputInterface $output, bool $enabled, bool $toggled): void
    {
        if ('json' === $input->getOption('format')) {
            $output->writeln(json_encode(
                [
                    'enabled' => $enabled,
                    'maintenanceFilePath' => $this->maintenanceFilePath,
                ],
                JSON_THROW_ON_ERROR
            ));

            return;
        }

        $io = new SymfonyStyle($input, $output);
        $message = 'Maintenance mode '.($toggled ? '' : 'is ').($enabled ? 'enabled' : 'disabled');

        if ($toggled) {
            $io->success($message);
        } elseif ($enabled) {
            $io->note($message);
        } else {
            $io->info($message);
        }
    }
}
