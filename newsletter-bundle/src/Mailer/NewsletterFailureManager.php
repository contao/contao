<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Mailer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Symfony\Component\Mime\Exception\RfcComplianceException;

class NewsletterFailureManager
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function recordFailure(int $newsletterId, string $address, \Throwable $error): void
    {
        $time = time();
        $message = $error->getMessage();

        $this->connection->executeStatement(
            'INSERT INTO tl_newsletter_failures SET pid = ?, tstamp = ?, email = ?, error = ? ON DUPLICATE KEY UPDATE tstamp = ?, error = ?',
            [$newsletterId, $time, $address, $message, $time, $message],
        );

        if ($this->isPermanentRecipientFailure($error)) {
            $this->connection->executeStatement(
                'UPDATE tl_newsletter_recipients SET active = :active WHERE email = :email AND pid = (SELECT pid FROM tl_newsletter WHERE id = :newsletter)',
                ['active' => false, 'email' => $address, 'newsletter' => $newsletterId],
                ['active' => Types::BOOLEAN],
            );
        }
    }

    public function clearFailure(int $newsletterId, string $address): void
    {
        $this->connection->delete('tl_newsletter_failures', ['pid' => $newsletterId, 'email' => $address]);
    }

    private function isPermanentRecipientFailure(\Throwable $error): bool
    {
        if ($error instanceof RfcComplianceException) {
            return true;
        }

        if (!$error instanceof UnexpectedResponseException || !preg_match('/\b(?<status>5\.\d+\.\d+)\b/', $error->getMessage(), $matches)) {
            return false;
        }

        // Permanent destination address or disabled mailbox statuses (RFC 3463)
        return \in_array(
            $matches['status'],
            [
                '5.1.1',
                '5.1.2',
                '5.1.3',
                '5.1.6',
                '5.2.1',
            ],
            true,
        );
    }
}
