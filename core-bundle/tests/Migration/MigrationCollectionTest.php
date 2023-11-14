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
        $results = $migrations->run();

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

    public function getMigrationServices(): array
    {
        return [
            new class() extends AbstractMigration {
                #[\Override]
                public function getName(): string
                {
                    return 'Successful Migration';
                }

                #[\Override]
                public function shouldRun(): bool
                {
                    return true;
                }

                #[\Override]
                public function run(): MigrationResult
                {
                    return $this->createResult(true, 'successful');
                }
            },
            new class() extends AbstractMigration {
                #[\Override]
                public function getName(): string
                {
                    return 'Failing Migration';
                }

                #[\Override]
                public function shouldRun(): bool
                {
                    return true;
                }

                #[\Override]
                public function run(): MigrationResult
                {
                    return $this->createResult(false, 'failing');
                }
            },
            new class() extends AbstractMigration {
                #[\Override]
                public function getName(): string
                {
                    return 'Inactive Migration';
                }

                #[\Override]
                public function shouldRun(): bool
                {
                    return false;
                }

                #[\Override]
                public function run(): MigrationResult
                {
                    throw new \LogicException('Should never be executed');
                }
            },
        ];
    }
}
