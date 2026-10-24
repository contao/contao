<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Migration;

use Contao\CoreBundle\EventListener\DataContainer\HighlightLanguageListener;
use Contao\CoreBundle\Migration\Version601\HighlightLanguageMigration;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

class HighlightLanguageMigrationTest extends TestCase
{
    public function testDoesNotRunIfTableDoesNotExist(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_content'])
            ->willReturn(false)
        ;

        $schemaManager
            ->expects($this->never())
            ->method('introspectTableByUnquotedName')
        ;

        $db = $this->createMock(Connection::class);
        $db
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $db
            ->expects($this->never())
            ->method('fetchOne')
        ;

        $migration = new HighlightLanguageMigration($db);

        $this->assertFalse($migration->shouldRun());
    }

    public function testDoesNotRunIfFieldDoesNotExist(): void
    {
        $db = $this->mockConnectionWithTable(new Table('tl_content'));
        $db
            ->expects($this->never())
            ->method('fetchOne')
        ;

        $migration = new HighlightLanguageMigration($db);

        $this->assertFalse($migration->shouldRun());
    }

    public function testDoesNotRunIfThereAreNoLegacyLanguages(): void
    {
        $db = $this->mockConnectionWithTable($this->mockTableWithHighlightColumn());
        $db
            ->expects($this->once())
            ->method('fetchOne')
            ->with(
                'SELECT TRUE FROM tl_content WHERE highlight IN (?) LIMIT 1',
                [array_keys(HighlightLanguageMigration::MAPPING)],
                [ArrayParameterType::STRING],
            )
            ->willReturn(false)
        ;

        $migration = new HighlightLanguageMigration($db);

        $this->assertFalse($migration->shouldRun());
    }

    public function testRunsIfThereAreLegacyLanguages(): void
    {
        $db = $this->mockConnectionWithTable($this->mockTableWithHighlightColumn());
        $db
            ->expects($this->once())
            ->method('fetchOne')
            ->willReturn(true)
        ;

        $migration = new HighlightLanguageMigration($db);

        $this->assertTrue($migration->shouldRun());
    }

    public function testUpdatesEveryLegacyLanguage(): void
    {
        $updates = [];

        $db = $this->createMock(Connection::class);
        $db
            ->expects($this->exactly(\count(HighlightLanguageMigration::MAPPING)))
            ->method('update')
            ->willReturnCallback(
                static function (string $table, array $data, array $criteria) use (&$updates): int {
                    self::assertSame('tl_content', $table);

                    $updates[$criteria['highlight']] = $data['highlight'];

                    return 1;
                },
            )
        ;

        $migration = new HighlightLanguageMigration($db);

        $this->assertTrue($migration->run()->isSuccessful());
        $this->assertSame(HighlightLanguageMigration::MAPPING, $updates);
    }

    #[DataProvider('getMapping')]
    public function testMapsToASupportedLanguage(string $legacy, string $language): void
    {
        $this->assertArrayHasKey(
            $language,
            HighlightLanguageListener::LANGUAGES,
            \sprintf('The language "%s" (mapped from "%s") is not supported by highlight.js.', $language, $legacy),
        );
    }

    public static function getMapping(): iterable
    {
        foreach (HighlightLanguageMigration::MAPPING as $legacy => $language) {
            yield $legacy => [$legacy, $language];
        }
    }

    private function mockTableWithHighlightColumn(): Table
    {
        return new Table('tl_content', [new Column('highlight', Type::getType(Types::STRING))]);
    }

    private function mockConnectionWithTable(Table $table): Connection&MockObject
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_content'])
            ->willReturn(true)
        ;

        $schemaManager
            ->expects($this->once())
            ->method('introspectTableByUnquotedName')
            ->with('tl_content')
            ->willReturn($table)
        ;

        $db = $this->createMock(Connection::class);
        $db
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        return $db;
    }
}
