<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional;

use Contao\System;
use Contao\TestCase\FunctionalTestCase;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class MigrateCommandTest extends FunctionalTestCase
{
    protected function tearDown(): void
    {
        self::resetDatabaseSchema();

        parent::tearDown();
    }

    public function testFixesTooLargeInnodbRowSize(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        System::setContainer($container);

        self::resetDatabaseSchema();

        $sql = 'ALTER TABLE tl_content ';

        $sql .= implode(
            ', ',
            array_map(
                static fn ($i) => "ADD test_$i binary(255) NULL",
                range(1, 50),
            ),
        );

        /** @var Connection $connection */
        $connection = $container->get('doctrine')->getConnection();

        // Disabling InnoDB strict mode makes it possible to create too large tables
        $connection->executeStatement('SET SESSION innodb_strict_mode = 0');
        $connection->executeStatement($sql);
        $connection->executeStatement('ALTER TABLE tl_content DROP text');
        $connection->executeStatement('SET SESSION innodb_strict_mode = 1');

        $output = new BufferedOutput();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $applicationArgs = ['', 'contao:migrate', '--no-interaction', '--schema-only', '--no-backup'];
        $exitCode = $application->run(new ArgvInput($applicationArgs), $output);
        $commandOutput = $output->fetch();

        $this->assertSame(0, $exitCode, "Command contao:migrate failed with exit code $exitCode:\n".$commandOutput);

        $this->assertStringContainsString(
            'The row size of table tl_content is too large',
            $commandOutput,
            'Without deletes the table is too large and a warning should be shown',
        );

        $columns = $connection->createSchemaManager()->listTableColumns('tl_content');
        $this->assertArrayHasKey('test_1', $columns);
        $this->assertArrayHasKey('test_50', $columns);
        $this->assertArrayHasKey('text', $columns);

        $connection->executeStatement('SET SESSION innodb_strict_mode = 0');
        $connection->executeStatement('ALTER TABLE tl_content DROP text');
        $connection->executeStatement('SET SESSION innodb_strict_mode = 1');

        $application = new Application($kernel);
        $application->setAutoExit(false);
        $applicationArgs[] = '--with-deletes';
        $exitCode = $application->run(new ArgvInput($applicationArgs), $output);
        $commandOutput = $output->fetch();

        $this->assertSame(0, $exitCode, "Command contao:migrate failed with exit code $exitCode:\n".$commandOutput);

        $this->assertStringNotContainsString(
            'The row size of table tl_content is too large',
            $commandOutput,
            'With deletes the table is small enough and no warning should be shown',
        );

        $columns = $connection->createSchemaManager()->listTableColumns('tl_content');
        $this->assertArrayNotHasKey('test_1', $columns);
        $this->assertArrayNotHasKey('test_50', $columns);
        $this->assertArrayHasKey('text', $columns);
    }
}
