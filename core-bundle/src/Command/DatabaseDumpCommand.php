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

use Contao\CoreBundle\Doctrine\Dumper\DatabaseDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Dumps the database.
 *
 * @internal
 */
class DatabaseDumpCommand extends Command
{
    protected static $defaultName = 'contao:database:dump';

    private DatabaseDumper $databaseDumper;

    public function __construct(DatabaseDumper $databaseDumper)
    {
        $this->databaseDumper = $databaseDumper;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setAliases(['contao:db:dump'])
            ->addArgument('targetPath', InputArgument::OPTIONAL, 'The path to the SQL dump.')
            ->addOption('bufferSize', 'b', InputOption::VALUE_OPTIONAL, 'Maximum length of a single SQL statement generated. Requires said amount of RAM. Defaults to "100MB".')
            ->addOption('ignoreTables', 'i', InputOption::VALUE_OPTIONAL, 'A comma-separated list of database tables to ignore. Defaults to the Contao configuration (contao.db.dump.ignoreTables).')
            ->setDescription('Dumps an database to a given target file.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $config = $this->databaseDumper->createDefaultConfig();

        if ($targetPath = $input->getArgument('targetPath')) {
            $config = $config->withTargetPath($targetPath);
        }

        if ($bufferSize = $input->getOption('bufferSize')) {
            $bufferSize = $this->parseBufferSize($bufferSize);
            $config = $config->withBufferSize($bufferSize);
        }

        if ($tablesToIgnore = $input->getOption('ignoreTables')) {
            $config = $config->withTablesToIgnore(explode(',', $tablesToIgnore));
        }

        $this->databaseDumper->dump($config);

        $io->success(
            sprintf(
                'Successfully created an SQL dump at "%s" while ignoring following tables: %s.',
                $config->getTargetPath(),
                implode(', ', $config->getTablesToIgnore())
            )
        );

        return 0;
    }

    private function parseBufferSize(string $bufferSize): ?int
    {
        $match = preg_match('/^(\d+)(KB|MB|GB)?$/', $bufferSize, $matches);

        if (false === $match || 0 === $match) {
            throw new \InvalidArgumentException('The buffer size must be an unsigned integer, optionally ending with KB, MB or GB.');
        }
        $bufferSize = (int) $matches[1];
        $bufferFactor = 1;

        switch ($matches[2]) {
            case 'GB':
                $bufferFactor *= 1024;
            // no break
            case 'MB':
                $bufferFactor *= 1024;
            // no break
            case 'KB':
                $bufferFactor *= 1024;
        }

        return $bufferSize * $bufferFactor;
    }
}
