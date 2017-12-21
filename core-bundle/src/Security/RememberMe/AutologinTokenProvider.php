<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security\RememberMe;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type as DoctrineType;
use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentToken;
use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentTokenInterface;
use Symfony\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

class AutologinTokenProvider implements TokenProviderInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function loadTokenBySeries($series): PersistentToken
    {
        $sql = '
            SELECT
                class, username, value, lastUsed
            FROM
                tl_remember_me
            WHERE
                series=:series
        ';

        $values = [
            'series' => $series,
        ];

        $types = [
            'series' => \PDO::PARAM_STR,
        ];

        $stmt = $this->connection->executeQuery($sql, $values, $types);

        if (!$row = $stmt->fetch(\PDO::FETCH_OBJ)) {
            throw new TokenNotFoundException('No token found.');
        }

        return new PersistentToken($row->class, $row->username, $series, $row->value, new \DateTime($row->lastUsed));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteTokenBySeries($series): void
    {
        $sql = '
            DELETE FROM
                tl_remember_me
            WHERE
                series=:series
        ';

        $values = [
            'series' => $series,
        ];

        $types = [
            'series' => \PDO::PARAM_STR,
        ];

        $this->connection->executeUpdate($sql, $values, $types);
    }

    /**
     * {@inheritdoc}
     */
    public function updateToken($series, $tokenValue, \DateTime $lastUsed): void
    {
        $sql = '
            UPDATE
                tl_remember_me
            SET
                value=:value, lastUsed=:lastUsed
            WHERE
                series=:series
        ';

        $values = [
            'value' => $tokenValue,
            'lastUsed' => $lastUsed,
            'series' => $series,
        ];

        $types = [
            'value' => \PDO::PARAM_STR,
            'lastUsed' => DoctrineType::DATETIME,
            'series' => \PDO::PARAM_STR,
        ];

        $updated = $this->connection->executeUpdate($sql, $values, $types);

        if ($updated < 1) {
            throw new TokenNotFoundException('No token found.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createNewToken(PersistentTokenInterface $token): void
    {
        $sql = '
            INSERT INTO
                tl_remember_me
                (class, username, series, value, lastUsed)
            VALUES
                (:class, :username, :series, :value, :lastUsed)
        ';

        $values = [
            'class' => $token->getClass(),
            'username' => $token->getUsername(),
            'series' => $token->getSeries(),
            'value' => $token->getTokenValue(),
            'lastUsed' => $token->getLastUsed(),
        ];

        $types = [
            'class' => \PDO::PARAM_STR,
            'username' => \PDO::PARAM_STR,
            'series' => \PDO::PARAM_STR,
            'value' => \PDO::PARAM_STR,
            'lastUsed' => DoctrineType::DATETIME,
        ];

        $this->connection->executeUpdate($sql, $values, $types);
    }
}
