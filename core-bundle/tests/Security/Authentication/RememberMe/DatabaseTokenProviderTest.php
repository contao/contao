<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Authentication\RememberMe;

use Contao\CoreBundle\Security\Authentication\RememberMe\DatabaseTokenProvider;
use Contao\CoreBundle\Security\Authentication\RememberMe\PersistentToken;
use Contao\FrontendUser;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type as DoctrineType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

class DatabaseTokenProviderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $provider = new DatabaseTokenProvider($this->createMock(Connection::class), 'secret');

        $this->assertInstanceOf(
            'Contao\CoreBundle\Security\Authentication\RememberMe\DatabaseTokenProvider',
            $provider
        );
    }

    public function testLoadsATokenByItsSeries(): void
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
            'series' => hash_hmac('sha256', 'series', 'secret'),
        ];

        $types = [
            'series' => \PDO::PARAM_STR,
        ];

        $row = new \stdClass();
        $row->class = FrontendUser::class;
        $row->username = 'foobar';
        $row->value = 'value';
        $row->lastUsed = 'now';

        $stmt = $this->createMock(Statement::class);
        $stmt
            ->expects($this->once())
            ->method('fetch')
            ->with(\PDO::FETCH_OBJ)
            ->willReturn($row)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with($sql, $values, $types)
            ->willReturn($stmt)
        ;

        $provider = new DatabaseTokenProvider($connection, 'secret');
        $token = $provider->loadTokenBySeries('series');

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\RememberMe\PersistentToken', $token);
        $this->assertSame(FrontendUser::class, $token->getClass());
        $this->assertSame('foobar', $token->getUsername());
        $this->assertSame('series', $token->getSeries());
        $this->assertSame('value', $token->getTokenValue());
        $this->assertSame(date('Y-m-d'), $token->getLastUsed()->format('Y-m-d'));
    }

    public function testFailsToLoadATokenIfTheSeriesDoesNotExist(): void
    {
        $stmt = $this->createMock(Statement::class);
        $stmt
            ->expects($this->once())
            ->method('fetch')
            ->willReturn(null)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($stmt)
        ;

        $provider = new DatabaseTokenProvider($connection, 'secret');

        $this->expectException(TokenNotFoundException::class);

        $provider->loadTokenBySeries('series');
    }

    public function testDeletesATokenByItsSeries(): void
    {
        $sql = '
            DELETE FROM
                tl_remember_me
            WHERE
                series=:series
        ';

        $values = [
            'series' => hash_hmac('sha256', 'series', 'secret'),
        ];

        $types = [
            'series' => \PDO::PARAM_STR,
        ];

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeUpdate')
            ->with($sql, $values, $types)
        ;

        $provider = new DatabaseTokenProvider($connection, 'secret');
        $provider->deleteTokenBySeries('series');
    }

    public function testUpdatesAToken(): void
    {
        $sql = '
            UPDATE
                tl_remember_me
            SET
                value=:value, lastUsed=:lastUsed
            WHERE
                series=:series
        ';

        $dateTime = new \DateTime('now');

        $values = [
            'value' => 'value',
            'lastUsed' => $dateTime,
            'series' => hash_hmac('sha256', 'series', 'secret'),
        ];

        $types = [
            'value' => \PDO::PARAM_STR,
            'lastUsed' => DoctrineType::DATETIME,
            'series' => \PDO::PARAM_STR,
        ];

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeUpdate')
            ->with($sql, $values, $types)
            ->willReturn(1)
        ;

        $provider = new DatabaseTokenProvider($connection, 'secret');
        $provider->updateToken('series', 'value', $dateTime);

        $this->addToAssertionCount(1); // does not throw an exception
    }

    public function testFailsToUpdateATokenIfTheSeriesDoesNotExist(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeUpdate')
            ->willReturn(0)
        ;

        $provider = new DatabaseTokenProvider($connection, 'secret');

        $this->expectException(TokenNotFoundException::class);

        $provider->updateToken('series', 'value', new \DateTime('now'));
    }

    public function testCreatesANewToken(): void
    {
        $sql = '
            INSERT INTO
                tl_remember_me
                (class, username, series, value, lastUsed)
            VALUES
                (:class, :username, :series, :value, :lastUsed)
        ';

        $token = new PersistentToken(FrontendUser::class, 'foobar', 'series', 'value', new \DateTime('now'));

        $values = [
            'class' => $token->getClass(),
            'username' => $token->getUsername(),
            'series' => hash_hmac('sha256', $token->getSeries(), 'secret'),
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

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeUpdate')
            ->with($sql, $values, $types)
            ->willReturn(1)
        ;

        $provider = new DatabaseTokenProvider($connection, 'secret');
        $provider->createNewToken($token);
    }
}
