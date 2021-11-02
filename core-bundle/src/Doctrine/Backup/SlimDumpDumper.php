<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Backup;

use Contao\CoreBundle\Doctrine\Backup\Config\CreateConfig;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Webfactory\Slimdump\Config\Config;
use Webfactory\Slimdump\Config\ConfigBuilder;
use Webfactory\Slimdump\DumpTask;

class SlimDumpDumper implements DumperInterface
{
    public function dump(Connection $connection, CreateConfig $config): void
    {
        $backup = $config->getBackup();
        $handle = fopen($backup->getFilepath(), 'w');
        $deflateContext = $config->isGzCompressionEnabled() ? deflate_init(ZLIB_ENCODING_GZIP, ['level' => 9]) : null;

        $output = $this->getDumperOutput($handle, $deflateContext);
        $output->writeln($config->getDumpHeader());
        $output->writeln('-- Generated at '.$backup->getCreatedAt()->format(\DateTimeInterface::ISO8601));

        try {
            $dumptask = new DumpTask($connection, $this->createSlimDumpConfig($connection, $config), true, true, $config->getBufferSize(), $output);
            $dumptask->dump();
        } catch (\Exception $e) {
            throw new BackupManagerException($e->getMessage(), 0, $e);
        }

        if ($deflateContext) {
            fwrite($handle, deflate_add($deflateContext, '', ZLIB_FINISH));
        }

        fclose($handle);
    }

    private function createSlimDumpConfig(Connection $connection, CreateConfig $config): Config
    {
        $tables = $connection->createSchemaManager()->listTables();
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

    private function getDumperOutput($handle, $deflateContext = null): OutputInterface
    {
        $output = new class($handle) extends StreamOutput {
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
