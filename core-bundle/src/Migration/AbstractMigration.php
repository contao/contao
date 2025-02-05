<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration;

abstract class AbstractMigration implements MigrationInterface
{
    public function getName(): string
    {
        return static::class;
    }

    protected function createResult(bool $successful, string|null $message = null): MigrationResult
    {
        $message ??= $this->getName().' '.($successful ? 'executed successfully' : 'execution failed');

        return new MigrationResult($successful, $message);
    }
}
