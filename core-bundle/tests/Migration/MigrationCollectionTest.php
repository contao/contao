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
use Contao\CoreBundle\Migration\MigrationInterface;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\CoreBundle\Migration\UnexpectedPendingMigrationException;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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

    public function testRunsAllPendingMigrations(): void
    {
        $state = new class() {
            public bool $firstRun = false;

            public bool $secondRun = false;
        };
        $firstMigration = $this->createMock(MigrationInterface::class);
        $firstMigration
            ->method('shouldRun')
            ->willReturnCallback(
                static fn (): bool => !$state->firstRun,
            )
        ;

        $firstMigration
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(
                static function () use ($state): MigrationResult {
                    $state->firstRun = true;

                    return new MigrationResult(true, 'successful');
                },
            )
        ;

        $secondMigration = $this->createMock(MigrationInterface::class);
        $secondMigration
            ->method('shouldRun')
            ->willReturnCallback(
                static fn (): bool => $state->firstRun && !$state->secondRun,
            )
        ;

        $secondMigration
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(
                static function () use ($state): MigrationResult {
                    $state->secondRun = true;

                    return new MigrationResult(true, 'successful');
                },
            )
        ;

        new MigrationCollection([$firstMigration, $secondMigration])->runAll();
    }

    public function testRestartsIfThePendingMigrationsChangeWhileRunning(): void
    {
        $state = (object) [
            'firstRun' => false,
            'deferredRun' => false,
            'lastRun' => false,
            'deferredChecks' => 0,
            'deferredRunAtCheck' => null,
            'runOrder' => [],
        ];

        $migrations = new MigrationCollection([
            $this->createChangingMigration($state, 'First Migration'),
            $this->createChangingMigration($state, 'Deferred Migration'),
            $this->createChangingMigration($state, 'Last Migration'),
        ]);

        $migrations->runAll();

        $this->assertSame(['First Migration', 'Deferred Migration', 'Last Migration'], $state->runOrder);
        $this->assertSame(4, $state->deferredRunAtCheck);
    }

    #[DataProvider('getUnexpectedPendingMigrations')]
    public function testRunMigrationsUnexpectedPending(array $pendingNames, string|null $expectedExceptionMessage): void
    {
        $migrations = new MigrationCollection($this->getMigrationServices());

        if (null !== $expectedExceptionMessage) {
            $this->expectException(UnexpectedPendingMigrationException::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $results = $migrations->run($pendingNames);

        if ($results instanceof \Traversable) {
            iterator_to_array($results);
        }

        $this->assertNull($expectedExceptionMessage);
    }

    public static function getUnexpectedPendingMigrations(): iterable
    {
        yield [
            ['Successful Migration', 'Failing Migration', 'Inactive Migration'],
            'Expected "Inactive Migration" got no migration.',
        ];

        yield [
            ['Successful Migration'],
            null,
        ];

        yield [
            ['Failing Migration', 'Successful Migration'],
            'Expected "Failing Migration" got "Successful Migration".',
        ];

        yield [
            ['Successful Migration', 'Different Migration'],
            'Expected "Different Migration" got "Failing Migration".',
        ];

        yield [
            [],
            null,
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

    private function createChangingMigration(object $state, string $name): MigrationInterface
    {
        return new class($state, $name) extends AbstractMigration {
            public function __construct(
                private readonly object $state,
                private readonly string $name,
            ) {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function shouldRun(): bool
            {
                if ('Deferred Migration' === $this->name) {
                    ++$this->state->deferredChecks;
                }

                return match ($this->name) {
                    'First Migration' => !$this->state->firstRun,
                    'Deferred Migration' => $this->state->firstRun && !$this->state->deferredRun,
                    'Last Migration' => !$this->state->lastRun,
                    default => throw new \LogicException('Unexpected migration name.'),
                };
            }

            public function run(): MigrationResult
            {
                match ($this->name) {
                    'First Migration' => $this->state->firstRun = true,
                    'Deferred Migration' => $this->state->deferredRun = true,
                    'Last Migration' => $this->state->lastRun = true,
                    default => throw new \LogicException('Unexpected migration name.'),
                };

                if ('Deferred Migration' === $this->name) {
                    $this->state->deferredRunAtCheck = $this->state->deferredChecks;
                }

                $this->state->runOrder[] = $this->getName();

                return $this->createResult(true);
            }
        };
    }
}
