<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Repository;

use Contao\CoreBundle\Entity\Altcha;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * @template-extends ServiceEntityRepository<Altcha>
 *
 * @internal
 */
class AltchaRepository extends ServiceEntityRepository
{
    private readonly Connection $connection;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Altcha::class);

        /** @var Connection $connection */
        $connection = $registry->getConnection();

        $this->connection = $connection;
    }

    public function purgeExpiredChallenges(): void
    {
        $table = $this->getClassMetadata()->getTableName();

        $this->connection->executeStatement(
            "DELETE FROM $table WHERE solved = :solved OR expires < :expires",
            [
                'solved' => true,
                'expires' => new \DateTimeImmutable('now'),
            ],
            [
                'solved' => true,
                'expires' => Types::DATETIME_IMMUTABLE,
            ],
        );
    }

    public function isReplay(string $challenge): bool
    {
        $table = $this->getClassMetadata()->getTableName();

        return false !== $this->connection->fetchOne(
            "SELECT id FROM $table WHERE challenge = :challenge AND solved = :solved",
            [
                'challenge' => $challenge,
                'solved' => true,
            ],
            [
                'challenge' => Types::STRING,
                'solved' => Types::BOOLEAN,
            ],
        );
    }

    public function markChallengeAsSolved(string $challenge): int|string
    {
        $table = $this->getClassMetadata()->getTableName();

        return $this->connection->executeStatement(
            "UPDATE $table SET solved = :solved_true WHERE challenge = :challenge AND expires > :expires AND solved = :solved_false",
            [
                'solved_true' => true,
                'challenge' => $challenge,
                'expires' => new \DateTimeImmutable(),
                'solved_false' => false,
            ],
            [
                'solved_true' => Types::BOOLEAN,
                'challenge' => Types::STRING,
                'expires' => Types::DATE_IMMUTABLE,
                'solved_false' => Types::BOOLEAN,
            ],
        );
    }
}
