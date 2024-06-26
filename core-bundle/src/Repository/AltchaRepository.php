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
            "DELETE FROM $table WHERE expires < :expires",
            ['expires' => new \DateTimeImmutable()],
            ['expires' => Types::DATETIME_IMMUTABLE],
        );
    }

    public function isReplay(string $challenge): bool
    {
        $table = $this->getClassMetadata()->getTableName();

        return false !== $this->connection->fetchOne(
            "SELECT id FROM $table WHERE challenge = :challenge",
            ['challenge' => $challenge],
            ['challenge' => Types::STRING],
        );
    }
}
