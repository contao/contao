<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional\Migration;

use Contao\CoreBundle\Migration\Version410\RoutingMigration;
use Contao\TestCase\FunctionalTestCase;
use Doctrine\DBAL\Connection;

class RoutingMigrationTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        static::bootKernel();
    }

    /**
     * @dataProvider shouldRunProvider
     */
    public function testShouldRun(array $dropFields, bool $expected): void
    {
        static::resetDatabaseSchema();

        /** @var Connection $connection */
        $connection = static::$container->get('database_connection');

        foreach ($dropFields as $field) {
            $connection->exec('ALTER TABLE tl_page DROP '.$field);
        }

        $migration = new RoutingMigration($connection);

        $this->assertSame($expected, $migration->shouldRun());
    }

    public function shouldRunProvider(): \Generator
    {
        yield 'should not run if both fields exist' => [
            [],
            false,
        ];

        yield 'should not run if urlSuffix exist' => [
            ['urlPrefix'],
            false,
        ];

        yield 'should not run if urlPrefix exist' => [
            ['urlSuffix'],
            false,
        ];

        yield 'should run if both fields do not exist' => [
            ['urlPrefix', 'urlSuffix'],
            true,
        ];
    }

    public function testMigratesSchema(): void
    {
        static::resetDatabaseSchema();

        /** @var Connection $connection */
        $connection = static::$container->get('database_connection');
        $connection->exec('ALTER TABLE tl_page DROP urlPrefix, DROP urlSuffix');

        $columns = $connection->getSchemaManager()->listTableColumns('tl_page');

        $this->assertFalse(isset($columns['urlPrefix']));
        $this->assertFalse(isset($columns['urlsuffix']));

        $migration = new RoutingMigration($connection);
        $result = $migration->run();

        $this->assertTrue($result->isSuccessful());

        $columns = $connection->getSchemaManager()->listTableColumns('tl_page');

        $this->assertTrue(isset($columns['urlprefix']));
        $this->assertTrue(isset($columns['urlsuffix']));
    }

    /**
     * @dataProvider migrationDataProvider
     */
    public function testMigratesData(bool $prependLocale, string $urlSuffix): void
    {
        static::resetDatabaseSchema();
        static::loadFixtures([__DIR__.'/../../Fixtures/Functional/Migration/routing.yml'], false);

        /** @var Connection $connection */
        $connection = static::$container->get('database_connection');
        $connection->exec('ALTER TABLE tl_page DROP urlPrefix, DROP urlSuffix');

        $migration = new RoutingMigration($connection, $urlSuffix, $prependLocale);
        $migration->run();

        $rows = $connection->fetchAll('SELECT type, language, urlPrefix, urlSuffix FROM tl_page');

        foreach ($rows as $row) {
            if ('root' !== $row['type']) {
                $this->assertSame('', $row['urlPrefix']);
                $this->assertSame('.html', $row['urlSuffix']);
                continue;
            }

            $this->assertSame($prependLocale ? $row['language'] : '', $row['urlPrefix']);
            $this->assertSame($urlSuffix, $row['urlSuffix']);
        }
    }

    public function migrationDataProvider(): \Generator
    {
        yield [
            false,
            '.html',
        ];

        yield [
            true,
            '.html',
        ];

        yield [
            false,
            '.json',
        ];

        yield [
            true,
            '.json',
        ];

        yield [
            false,
            '/',
        ];

        yield [
            true,
            '/',
        ];

        yield [
            false,
            '',
        ];

        yield [
            true,
            '',
        ];
    }
}
