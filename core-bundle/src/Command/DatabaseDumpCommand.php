<?php

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
use Doctrine\DBAL\Schema\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webfactory\Slimdump\Config\Config;
use Webfactory\Slimdump\Config\ConfigBuilder;
use Webfactory\Slimdump\DumpTask;

/**
 * Dumps the database.
 *
 * @internal
 */
class DatabaseDumpCommand extends Command
{
    protected static $defaultName = 'contao:database:dump';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setAliases(['contao:db:dump'])
            ->addArgument('file', InputArgument::REQUIRED, 'The path to the SQL dump.')
            ->addOption('buffer-size', 'b', InputOption::VALUE_OPTIONAL, 'Maximum length of a single SQL statement generated. Requires said amount of RAM. Defaults to "100MB".')
            ->addOption('ignore-tables', 'i', InputOption::VALUE_OPTIONAL, 'A comma-separated list of database tables to ignore. Defaults to the Contao configuration (contao.db.dump.ignoreTables).')
            ->setDescription('Dumps an database to a given target file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $file = $input->getArgument('file');
        $bufferSize = $this->parseBufferSize($input->getOption('buffer-size') ?: '100MB');
        $tablesToIgnore = $input->getOption('ignore-tables') ? explode(',', $input->getOption('ignore-tables')) : ['tl_crawl_queue', 'tl_log', 'tl_search', 'tl_search_index', 'tl_search_term']; // TODO: Make this a bundle config (e.g. contao.db.dump.ignoreTable or something similar)
        $config = $this->createConfig($tablesToIgnore);
        $enableGzCompression = strcasecmp(substr($file, -3), '.gz') === 0;
        $handler = fopen($file, 'w');
        $deflateContext = $enableGzCompression ? deflate_init(ZLIB_ENCODING_GZIP, ['level' => 9]) : null;

        $output = $this->getOutput($handler, $deflateContext);

        $dumptask = new DumpTask($this->connection, $config, true, true, $bufferSize, $output);
        $dumptask->dump();

        if ($deflateContext) {
            fwrite($handler, deflate_add($deflateContext, '', ZLIB_FINISH));
        }

        fclose($handler);

        $io->success(sprintf('Successfully created an SQL dump while ignoring following tables: %s.', implode(', ', $tablesToIgnore)));

        return 0;
    }

    private function createConfig(array $tablesToIgnore = []): Config
    {
        $tables = $this->connection->getSchemaManager()->listTables();
        $tableNames = array_map(function(Table $table) {
            return $table->getName();
        }, $tables);

        $tableNames = array_diff($tableNames, $tablesToIgnore);

        $doc = new \DOMDocument();
        $slimDump = $doc->createElement('slimdump');

        foreach ($tableNames as $tableName) {
            $table = $doc->createElement('table');
            $table->setAttribute('name', $tableName);
            $table->setAttribute('dump', 'full');
            $slimDump->appendChild($table);
        }

        $doc->appendChild($slimDump);

        return ConfigBuilder::createFromXmlString($doc->saveXML());
    }

    private function getOutput($handler, $deflateContext = null): OutputInterface
    {
        $output = new class($handler) extends StreamOutput {
            private $deflateContext = null;

            public function setDeflateContext($deflateContext)
            {
                $this->deflateContext = $deflateContext;
            }

            protected function doWrite(string $message, bool $newline)
            {
                if ($newline) {
                    $message .= \PHP_EOL;
                }

                if ($this->deflateContext) {
                    $message = deflate_add($this->deflateContext, $message, ZLIB_NO_FLUSH);
                }

                parent::doWrite($message, false);
            }
        };

        $output->setDeflateContext($deflateContext);

        return $output;
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
