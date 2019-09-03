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

interface MigrationInterface
{
    public function getName(): string;

    public function shouldRun(): bool;

    public function run(): MigrationResult;
}
