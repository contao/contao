<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Command\BackendSearch;

use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\IndexUpdateConfig\UpdateAllProvidersConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @experimental
 */
#[AsCommand(name: 'contao:backend-search:index', description: '')]
class IndexCommand extends Command
{
    public function __construct(private readonly BackendSearch $backendSearch)
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addOption('update-since', null, InputOption::VALUE_REQUIRED, 'The start date of the data to consider since.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $updateSince = $input->getOption('update-since') ? new \DateTimeImmutable($input->getOption('update-since')) : null;
        } catch (\Exception $e) {
            $io->error('Invalid date format: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->backendSearch->triggerUpdate(new UpdateAllProvidersConfig($updateSince));

        return Command::SUCCESS;
    }
}
