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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Imports.
 *
 * @internal
 */
class DatabaseImportCommand extends Command
{
    protected static $defaultName = 'contao:database:import';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setAliases(['contao:db:import'])
            ->addOption('truncate', 't', InputOption::VALUE_NONE, 'Truncate the existing database')
            ->addArgument('file', InputArgument::REQUIRED, 'The path to the SQL dump.')
            ->setDescription('Imports an SQL dump directly to the database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('truncate')) {
            try {
                $this->truncate();
            } catch (Exception $e) {
                $io->error('Could not truncate database: '.$e->getMessage());

                return 1;
            }

            $io->success('Successfully truncated existing data.');
        }

        $file = $input->getArgument('file');
        $handle = strcasecmp(substr($file, -3), '.gz') ? fopen($file, 'r') : gzopen($file, 'rb');

        if (!$handle) {
            $io->error('SQL dump file not found.');

            return 1;
        }

        try {
            $this->import($handle);
        } catch (Exception $e) {
            $io->error('Could not import SQL dump: '.$e->getMessage());

            return 1;
        }

        $io->success('Successfully imported SQL dump.');

        return 0;
    }

    /**
     * @param resource $handle
     *
     * @throws Exception
     */
    private function import($handle): void
    {
        $sql = '';
        $delimiter = ';';

        while ($s = fgets($handle)) {
            if ('DELIMITER ' === strtoupper(substr($s, 0, 10))) {
                $delimiter = trim(substr($s, 10));
                continue;
            }

            if (substr($ts = rtrim($s), -\strlen($delimiter)) === $delimiter) {
                $sql .= substr($ts, 0, -\strlen($delimiter));
                $this->connection->executeQuery($sql);
                $sql = '';
                continue;
            }

            $sql .= $s;
        }

        if ('' !== rtrim($sql)) {
            $this->connection->executeQuery($sql);
        }
    }

    /**
     * @throws Exception
     */
    private function truncate(): void
    {
        $tables = $this->connection->getSchemaManager()->listTableNames();

        foreach ($tables as $table) {
            if (0 === strncmp($table, 'tl_', 3)) {
                $this->connection->executeStatement('TRUNCATE TABLE '.$this->connection->quoteIdentifier($table));
            }
        }
    }
}
