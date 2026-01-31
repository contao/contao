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

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationCollection;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\CoreBundle\Migration\UnexpectedPendingMigrationException;
use Contao\CoreBundle\Tests\TestCase;

class MigrationCollectionTest extends TestCase
{
    public function testHasPendingMigrations(): void
    {
        $migrations = new MigrationCollection($this->getMigrationServices());

        $this->assertTrue($migrations->hasPending());
    }

    public function testGetPendingNames(): void
    {
        $migrations = new MigrationCollection($this->getMigrationServices());
        $pendingMigrations = $migrations->getPendingNames();

        if ($pendingMigrations instanceof \Traversable) {
            $pendingMigrations = iterator_to_array($pendingMigrations);
        }

        $this->assertSame(['Successful Migration', 'Failing Migration'], $pendingMigrations);
    }

    public function testRunMigrations(): void
    {
        $migrations = new MigrationCollection($this->getMigrationServices());
        $pendingMigrations = $migrations->getPendingNames();

        if ($pendingMigrations instanceof \Traversable) {
            $pendingMigrations = iterator_to_array($pendingMigrations);
        }

        $results = $migrations->run($pendingMigrations);

        if ($results instanceof \Traversable) {
            $results = iterator_to_array($results);
        }

        $this->assertCount(2, $results);
        $this->assertInstanceOf(MigrationResult::class, $results[0]);
        $this->assertTrue($results[0]->isSuccessful());
        $this->assertSame('successful', $results[0]->getMessage());
        $this->assertInstanceOf(MigrationResult::class, $results[1]);
        $this->assertFalse($results[1]->isSuccessful());
        $this->assertSame('failing', $results[1]->getMessage());
    }

    /**
     * @dataProvider getUnexpectedPendingMigrations
     */
    public function testRunMigrationsUnexpectedPending(array $pendingNames, string $expectedExceptionMessage): void
    {
        $migrations = new MigrationCollection($this->getMigrationServices());

        $this->expectException(UnexpectedPendingMigrationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $results = $migrations->run($pendingNames);

        if ($results instanceof \Traversable) {
            $results = iterator_to_array($results);
        }
    }

    public static function getUnexpectedPendingMigrations(): iterable
    {
        yield [
            ['Successful Migration', 'Failing Migration', 'Inactive Migration'],
            'Expected "Inactive Migration" got no migration.',
        ];

        yield [
            ['Successful Migration'],
            'Expected no migration got "Failing Migration".',
        ];

        yield [
            ['Failing Migration', 'Successful Migration'],
            'Expected "Failing Migration" got "Successful Migration".',
        ];

        yield [
            ['Successful Migration', 'Different Migration'],
            'Expected "Different Migration" got "Failing Migration".',
        ];
    }

    public function getMigrationServices(): array
    {
        return [
            new class() extends AbstractMigration {
                public function getName(): string
                {
                    return 'Successful Migration';
                }

                public function shouldRun(): bool
                {
                    return true;
                }

                public function run(): MigrationResult
                {
                    return $this->createResult(true, 'successful');
                }
            },
            new class() extends AbstractMigration {
                public function getName(): string
                {
                    return 'Failing Migration';
                }

                public function shouldRun(): bool
                {
                    return true;
                }

                public function run(): MigrationResult
                {
                    return $this->createResult(false, 'failing');
                }
            },
            new class() extends AbstractMigration {
                public function getName(): string
                {
                    return 'Inactive Migration';
                }

                public function shouldRun(): bool
                {
                    return false;
                }

                public function run(): MigrationResult
                {
                    throw new \LogicException('Should never be executed');
                }
            },
        ];
    }
}
