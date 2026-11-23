<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Tests\Mailer;

use Contao\NewsletterBundle\Mailer\NewsletterFailureManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\SQLite\Driver as SqliteDriver;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Symfony\Component\Mime\Exception\RfcComplianceException;

class NewsletterFailureManagerTest extends TestCase
{
    /**
     * @dataProvider failureProvider
     */
    public function testRecordsFailuresAndOnlyDeactivatesPermanentlyRejectedSubscriptions(\Throwable $error, bool $deactivate): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly($deactivate ? 2 : 1))
            ->method('executeStatement')
            ->willReturnCallback(
                function (string $sql, array $data, array $types = []) use ($error, $deactivate): int {
                    if ('INSERT INTO tl_newsletter_failures SET pid = ?, tstamp = ?, email = ?, error = ? ON DUPLICATE KEY UPDATE tstamp = ?, error = ?' === $sql) {
                        $this->assertSame([42, $data[1], 'recipient@example.com', $error->getMessage(), $data[1], $error->getMessage()], $data);
                        $this->assertEqualsWithDelta(time(), $data[1], 2);

                        return 1;
                    }

                    $this->assertTrue($deactivate);
                    $this->assertSame('UPDATE tl_newsletter_recipients SET active = :active WHERE email = :email AND pid = (SELECT pid FROM tl_newsletter WHERE id = :newsletter)', $sql);
                    $this->assertSame(['active' => false, 'email' => 'recipient@example.com', 'newsletter' => 42], $data);
                    $this->assertSame(['active' => Types::BOOLEAN], $types);

                    return 1;
                },
            )
        ;

        (new NewsletterFailureManager($connection))->recordFailure(42, 'recipient@example.com', $error);
    }

    public static function failureProvider(): iterable
    {
        foreach (['5.1.1', '5.1.2', '5.1.3', '5.1.6', '5.2.1'] as $status) {
            yield 'permanent recipient failure '.$status => [self::smtpError('550 '.$status.' Rejected'), true];
        }

        foreach (['421 4.7.0 Rate limit', '450 4.2.1 Temporarily disabled', '451 4.3.0 Try later', '550 5.7.1 Policy rejection', '550 5.2.2 Mailbox full', '552 5.2.3 Message too large', '550 5.1.7 Invalid sender', '550 5.1.10 Recipient address has null MX', '550 Rejected'] as $response) {
            yield $response => [self::smtpError($response), false];
        }

        yield 'connection failure' => [new TransportException('Connection refused'), false];
        yield 'other failure' => [new \RuntimeException('Failure'), false];
        yield 'recipient status without SMTP dialogue' => [new UnexpectedResponseException('550 5.1.1 Unknown recipient', 550), true];
        yield 'recipient status without an exception code' => [new UnexpectedResponseException('550 5.1.1 Unknown recipient'), true];
        yield 'multiline reply' => [self::smtpError("550-5.1.1 Unknown recipient\r\n550 5.1.1 Please check the address"), true];
    }

    /**
     * @dataProvider permanentFailureProvider
     */
    public function testDeactivatesOnlyMatchingSubscriptionUsingBooleanDatabaseType(\Throwable $error): void
    {
        $connection = $this->createDatabaseConnection();
        $connection->insert('tl_newsletter', ['id' => 42, 'pid' => 7]);
        $connection->insert('tl_newsletter_recipients', ['id' => 12, 'pid' => 7, 'email' => 'recipient@example.com', 'active' => 1]);
        $connection->insert('tl_newsletter_recipients', ['id' => 13, 'pid' => 8, 'email' => 'recipient@example.com', 'active' => 1]);
        $connection->insert('tl_newsletter_recipients', ['id' => 14, 'pid' => 7, 'email' => 'another@example.com', 'active' => 1]);

        (new NewsletterFailureManager($connection))->recordFailure(42, 'recipient@example.com', $error);

        $this->assertSame([12 => 0, 13 => 1, 14 => 1], $connection->fetchAllKeyValue('SELECT id, active FROM tl_newsletter_recipients ORDER BY id'));
        $this->assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM tl_newsletter_failures'));
    }

    public static function permanentFailureProvider(): iterable
    {
        yield 'invalid address' => [new RfcComplianceException('Invalid address')];
        yield 'permanent SMTP rejection' => [self::smtpError('550 5.1.1 Unknown recipient')];
    }

    public function testRecordsFailureWithoutMatchingSubscription(): void
    {
        $connection = $this->createDatabaseConnection();
        $connection->insert('tl_newsletter', ['id' => 42, 'pid' => 7]);
        $connection->insert('tl_newsletter_recipients', ['id' => 12, 'pid' => 8, 'email' => 'invalid-email', 'active' => 1]);

        (new NewsletterFailureManager($connection))->recordFailure(42, 'invalid-email', new RfcComplianceException('Invalid address'));

        $this->assertSame(1, (int) $connection->fetchOne('SELECT active FROM tl_newsletter_recipients WHERE id = 12'));
        $this->assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM tl_newsletter_failures'));
    }

    public function testKeepsLatestFailurePerNewsletterAndEmail(): void
    {
        $connection = $this->createDatabaseConnection();
        $manager = new NewsletterFailureManager($connection);

        $manager->recordFailure(42, 'recipient@example.com', new TransportException('First failure'));
        $id = (int) $connection->fetchOne('SELECT id FROM tl_newsletter_failures');

        $connection->update('tl_newsletter_failures', ['tstamp' => 1], ['id' => $id]);

        $manager->recordFailure(42, 'recipient@example.com', new TransportException('Latest failure'));

        $records = $connection->fetchAllAssociative('SELECT * FROM tl_newsletter_failures');
        $this->assertCount(1, $records);
        $this->assertSame($id, (int) $records[0]['id']);
        $this->assertSame('Latest failure', $records[0]['error']);
        $this->assertGreaterThan(1, (int) $records[0]['tstamp']);

        $manager->recordFailure(43, 'recipient@example.com', new TransportException('Another newsletter'));
        $manager->recordFailure(42, 'another@example.com', new TransportException('Another recipient'));

        $this->assertSame(3, (int) $connection->fetchOne('SELECT COUNT(*) FROM tl_newsletter_failures'));
        $this->assertSame('Latest failure', $connection->fetchOne('SELECT error FROM tl_newsletter_failures WHERE id = ?', [$id]));
    }

    public function testClearsOnlyMatchingFailure(): void
    {
        $connection = $this->createDatabaseConnection();
        $manager = new NewsletterFailureManager($connection);
        $manager->recordFailure(42, 'recipient@example.com', new TransportException('Failure'));
        $manager->recordFailure(43, 'recipient@example.com', new TransportException('Another newsletter'));
        $manager->recordFailure(42, 'another@example.com', new TransportException('Another recipient'));

        $manager->clearFailure(42, 'recipient@example.com');

        $this->assertSame(
            [
                ['pid' => 43, 'email' => 'recipient@example.com'],
                ['pid' => 42, 'email' => 'another@example.com'],
            ],
            $connection->fetchAllAssociative('SELECT pid, email FROM tl_newsletter_failures ORDER BY id'),
        );
    }

    private function createDatabaseConnection(): Connection
    {
        if (!\extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('The pdo_sqlite extension is required.');
        }

        // Translate MySQL's INSERT SET and upsert syntax for the SQLite regression tests
        $connection = new class(['memory' => true], new SqliteDriver()) extends Connection {
            public function executeStatement($sql, array $params = [], array $types = []): int|string
            {
                $sql = str_replace('INSERT INTO tl_newsletter_failures SET pid = ?, tstamp = ?, email = ?, error = ?', 'INSERT INTO tl_newsletter_failures (pid, tstamp, email, error) VALUES (?, ?, ?, ?)', $sql);

                return parent::executeStatement(str_replace('ON DUPLICATE KEY UPDATE', 'ON CONFLICT (pid, email) DO UPDATE SET', $sql), $params, $types);
            }
        };

        $connection->executeStatement('CREATE TABLE tl_newsletter_failures (id INTEGER PRIMARY KEY, pid INTEGER, tstamp INTEGER, email TEXT, error TEXT, UNIQUE (pid, email))');
        $connection->executeStatement('CREATE TABLE tl_newsletter (id INTEGER PRIMARY KEY, pid INTEGER)');
        $connection->executeStatement('CREATE TABLE tl_newsletter_recipients (id INTEGER PRIMARY KEY, pid INTEGER, email TEXT, active INTEGER NOT NULL CHECK (active IN (0, 1)), UNIQUE (pid, email))');

        return $connection;
    }

    private static function smtpError(string $response): UnexpectedResponseException
    {
        return new UnexpectedResponseException('Expected response code "250/251/252" but got code "'.substr($response, 0, 3).'", with message "'.$response.'".', (int) $response);
    }
}
