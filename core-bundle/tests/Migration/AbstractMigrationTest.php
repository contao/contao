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
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\CoreBundle\Tests\TestCase;

class AbstractMigrationTest extends TestCase
{
    public function testGetNameUsesClassName(): void
    {
        $migration = new class() extends AbstractMigration {
            #[\Override]
            public function shouldRun(): bool
            {
                return true;
            }

            #[\Override]
            public function run(): MigrationResult
            {
                return $this->createResult(true);
            }
        };

        $this->assertStringContainsString('anonymous', $migration->getName());
    }

    public function testCreateResultDefault(): void
    {
        $migration = new class() extends AbstractMigration {
            #[\Override]
            public function getName(): string
            {
                return 'Test Migration';
            }

            #[\Override]
            public function shouldRun(): bool
            {
                return true;
            }

            #[\Override]
            public function run(): MigrationResult
            {
                return $this->createResult(true);
            }
        };

        $this->assertTrue($migration->run()->isSuccessful());
        $this->assertStringContainsString('Test Migration', $migration->run()->getMessage());
        $this->assertStringContainsString('successful', $migration->run()->getMessage());
    }

    public function testCreateResultFailed(): void
    {
        $migration = new class() extends AbstractMigration {
            #[\Override]
            public function getName(): string
            {
                return 'Test Migration';
            }

            #[\Override]
            public function shouldRun(): bool
            {
                return true;
            }

            #[\Override]
            public function run(): MigrationResult
            {
                return $this->createResult(false);
            }
        };

        $this->assertFalse($migration->run()->isSuccessful());
        $this->assertStringContainsString('Test Migration', $migration->run()->getMessage());
        $this->assertStringContainsString('failed', $migration->run()->getMessage());
    }

    public function testCreateResultCustomMessage(): void
    {
        $migration = new class() extends AbstractMigration {
            #[\Override]
            public function getName(): string
            {
                return 'Test Migration';
            }

            #[\Override]
            public function shouldRun(): bool
            {
                return true;
            }

            #[\Override]
            public function run(): MigrationResult
            {
                return $this->createResult(true, 'Custom Message');
            }
        };

        $this->assertTrue($migration->run()->isSuccessful());
        $this->assertSame('Custom Message', $migration->run()->getMessage());
    }
}
