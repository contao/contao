<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Dumper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Filesystem\Filesystem;
use Webfactory\Slimdump\Config\ConfigBuilder;
use Webfactory\Slimdump\DumpTask;

/**
 * @internal
 * @final
 */
class Dumper
{
    private Connection $connection;

    public function __construct(Connection $connection, string $projectDir, array $tablesToIgnore)
    {
        $this->connection = $connection;
        $this->projectDir = $projectDir;
        $this->tablesToIgnore = $tablesToIgnore;
    }

    public function createDefaultConfig(): Config
    {
        $defaultFilename = sprintf('%s/var/cache/backups/db_dump_%s.sql.gz', $this->projectDir, date('dmY'));

        return (new Config($defaultFilename))
            ->withTablesToIgnore($this->tablesToIgnore)
        ;
    }

    public function dump(Config $config): void
    {
        // Ensure the target file exists and is empty
        (new Filesystem())->dumpFile($config->getTargetPath(), '');

        $handler = fopen($config->getTargetPath(), 'w');
        $deflateContext = $config->isGzCompressionEnabled() ? deflate_init(ZLIB_ENCODING_GZIP, ['level' => 9]) : null;

        $output = $this->getOutput($handler, $deflateContext);

        $dumptask = new DumpTask($this->connection, $this->createSlimDumpConfig($config), true, true, $config->getBufferSize(), $output);
        $dumptask->dump();

        if ($deflateContext) {
            fwrite($handler, deflate_add($deflateContext, '', ZLIB_FINISH));
        }

        fclose($handler);
    }

    private function createSlimDumpConfig(Config $config): \Webfactory\Slimdump\Config\Config
    {
        $tables = $this->connection->createSchemaManager()->listTables();
        $tableNames = array_map(static fn (Table $table) => $table->getName(), $tables);

        $tableNames = array_diff($tableNames, $config->getTablesToIgnore());

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
            private $deflateContext;

            public function setDeflateContext($deflateContext): void
            {
                $this->deflateContext = $deflateContext;
            }

            protected function doWrite(string $message, bool $newline): void
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
}
