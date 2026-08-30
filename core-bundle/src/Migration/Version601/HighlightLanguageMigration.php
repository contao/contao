<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version601;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

class HighlightLanguageMigration extends AbstractMigration
{
    public const array MAPPING = [
        'Apache' => 'apache',
        'Bash' => 'bash',
        'C#' => 'csharp',
        'C++' => 'cpp',
        'CSS' => 'css',
        'Diff' => 'diff',
        'HTML' => 'xml',
        'HTTP' => 'http',
        'Ini' => 'ini',
        'JSON' => 'json',
        'Java' => 'java',
        'JavaScript' => 'javascript',
        'Markdown' => 'markdown',
        'Nginx' => 'nginx',
        'Perl' => 'perl',
        'PHP' => 'php',
        'PowerShell' => 'powershell',
        'Python' => 'python',
        'Ruby' => 'ruby',
        'SCSS' => 'scss',
        'SQL' => 'sql',
        'Twig' => 'twig',
        'YAML' => 'yaml',
        'XML' => 'xml',
    ];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_content'])) {
            return false;
        }

        $table = $schemaManager->introspectTableByUnquotedName('tl_content');

        if (!$table->hasColumn('highlight')) {
            return false;
        }

        $test = $this->connection->fetchOne(
            'SELECT TRUE FROM tl_content WHERE highlight IN (?) LIMIT 1',
            [array_keys(self::MAPPING)],
            [ArrayParameterType::STRING],
        );

        return false !== $test;
    }

    public function run(): MigrationResult
    {
        foreach (self::MAPPING as $old => $new) {
            $this->connection->update('tl_content', ['highlight' => $new], ['highlight' => $old]);
        }

        return $this->createResult(true);
    }
}
