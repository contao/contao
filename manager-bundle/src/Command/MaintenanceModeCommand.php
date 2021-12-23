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
            ->setName('contao:maintenance-mode')
            ->addArgument('state', InputArgument::REQUIRED, 'Use "enable" to enable and "disable" to disable the maintenance mode. If the state is already the desired one, nothing happens. You can also use "on" and "off".')
            ->addOption('template', 't', InputOption::VALUE_REQUIRED, 'Allows to take a different Twig template name when enabling the maintenance mode.', '@ContaoCore/Error/service_unavailable.html.twig')
            ->addOption('templateVars', null, InputOption::VALUE_OPTIONAL, 'Add custom template variables to the Twig template when enabling the maintenance mode (provide as JSON).', '{}')
            ->setDescription('Changes the state of the system maintenance mode.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $shouldEnable = \in_array($input->getArgument('state'), ['enable', 'on'], true);
        $io = new SymfonyStyle($input, $output);

        if ($shouldEnable) {
            $this->enable($input->getOption('template'), $input->getOption('templateVars'));
            $io->success('Maintenance mode enabled.');
        } else {
            $this->disable();
            $io->success('Maintenance mode disabled.');
        }

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
}
