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

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\Version410\RoutingMigration;
use Contao\TestCase\FunctionalTestCase;
use Doctrine\DBAL\Connection;

class RoutingMigrationTest extends FunctionalTestCase
{
    /**
     * @dataProvider shouldRunProvider
     */
    public function testShouldRun(array $dropFields, bool $expected): void
    {
        static::bootKernel();
        static::resetDatabaseSchema();

        /** @var Connection $connection */
        $connection = static::$container->get('database_connection');

        foreach ($dropFields as $field) {
            $connection->exec('ALTER TABLE tl_page DROP '.$field);
        }

        /** @var ContaoFramework $framework */
        $framework = static::$container->get('contao.framework');

        $migration = new RoutingMigration($connection, $framework);

        $this->assertSame($expected, $migration->shouldRun());
    }

    public function shouldRunProvider(): \Generator
    {
        yield 'should not run if all fields exist' => [
            [],
            false,
        ];

        yield 'should not run if urlSuffix exist' => [
            ['urlPrefix', 'useFolderUrl'],
            false,
        ];

        yield 'should not run if urlPrefix exist' => [
            ['urlSuffix', 'useFolderUrl'],
            false,
        ];

        yield 'should not run if useFolderUrl exist' => [
            ['urlPrefix', 'urlSuffix'],
            false,
        ];

        yield 'should run if all fields do not exist' => [
            ['urlPrefix', 'urlSuffix', 'useFolderUrl'],
            true,
        ];
    }

    public function testMigratesSchema(): void
    {
        static::bootKernel();
        static::resetDatabaseSchema();

        /** @var Connection $connection */
        $connection = static::$container->get('database_connection');
        $connection->exec('ALTER TABLE tl_page DROP urlPrefix, DROP urlSuffix, DROP useFolderUrl');

        $columns = $connection->getSchemaManager()->listTableColumns('tl_page');

        $this->assertFalse(isset($columns['urlPrefix']));
        $this->assertFalse(isset($columns['urlsuffix']));
        $this->assertFalse(isset($columns['usefolderurl']));

        /** @var ContaoFramework $framework */
        $framework = static::$container->get('contao.framework');

        $migration = new RoutingMigration($connection, $framework);
        $result = $migration->run();

        $this->assertTrue($result->isSuccessful());

        $columns = $connection->getSchemaManager()->listTableColumns('tl_page');

        $this->assertTrue(isset($columns['urlprefix']));
        $this->assertTrue(isset($columns['urlsuffix']));
        $this->assertTrue(isset($columns['usefolderurl']));
    }

    /**
     * @dataProvider migrationDataProvider
     */
    public function testMigratesData(bool $prependLocale, string $urlSuffix, bool $folderUrl): void
    {
        static::bootKernel();
        static::resetDatabaseSchema();
        static::loadFixtures([__DIR__.'/../../Fixtures/Functional/Migration/routing.yml'], false);

        /** @var Connection $connection */
        $connection = static::$container->get('database_connection');
        $connection->exec('ALTER TABLE tl_page DROP urlPrefix, DROP urlSuffix, DROP useFolderUrl');

        /** @var ContaoFramework $framework */
        $framework = static::$container->get('contao.framework');

        /** @var Config $config */
        $config = $framework->getAdapter(Config::class);
        $config->set('folderUrl', $folderUrl);

        $migration = new RoutingMigration($connection, $framework, $urlSuffix, $prependLocale);
        $migration->run();

        $rows = $connection->fetchAll('SELECT type, language, urlPrefix, urlSuffix, useFolderUrl FROM tl_page');

        foreach ($rows as $row) {
            if ('root' !== $row['type']) {
                $this->assertSame('', $row['urlPrefix']);
                $this->assertSame('.html', $row['urlSuffix']);
                $this->assertSame('', $row['useFolderUrl']);
                continue;
            }

            $this->assertSame($prependLocale ? $row['language'] : '', $row['urlPrefix']);
            $this->assertSame($urlSuffix, $row['urlSuffix']);
            $this->assertSame($folderUrl ? '1' : '', $row['useFolderUrl']);
        }
    }

    public function migrationDataProvider(): \Generator
    {
        yield [
            false,
            '.html',
            true,
        ];

        yield [
            true,
            '.html',
            true,
        ];

        yield [
            false,
            '.json',
            true,
        ];

        yield [
            true,
            '.json',
            true,
        ];

        yield [
            false,
            '/',
            true,
        ];

        yield [
            true,
            '/',
            true,
        ];

        yield [
            false,
            '',
            true,
        ];

        yield [
            true,
            '',
            true,
        ];

        yield [
            false,
            '.html',
            true,
        ];

        yield [
            true,
            '.html',
            false,
        ];

        yield [
            false,
            '.json',
            false,
        ];

        yield [
            true,
            '.json',
            false,
        ];

        yield [
            false,
            '/',
            false,
        ];

        yield [
            true,
            '/',
            false,
        ];

        yield [
            false,
            '',
            false,
        ];

        yield [
            true,
            '',
            false,
        ];
    }
}
