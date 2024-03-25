<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

#[AsCronJob('hourly')]
class PurgeExpiredAltchaChallengesCron
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function __invoke(): void
    {
        $this->connection->executeStatement(
            'DELETE FROM tl_pow_altcha WHERE solved = :solved OR expires < :expires',
            [
                'solved' => true,
                'expires' => new \DateTime('now'),
            ],
            [
                'solved' => true,
                'expires' => Types::DATE_MUTABLE,
            ],
        );
    }
}
