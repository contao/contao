<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version404;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class Version447Update extends AbstractMigration
{
    private Connection $connection;
    private TranslatorInterface $translator;

    public function __construct(Connection $connection, TranslatorInterface $translator)
    {
        $this->connection = $connection;
        $this->translator = $translator;
    }

    public function getName(): string
    {
        return 'Contao 4.4.7 Update';
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_newsletter_recipients'])) {
            return false;
        }

        $columns = $schemaManager->listTableIndexes('tl_newsletter_recipients');

        return !isset($columns['pid_email']);
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->connection->createSchemaManager();

        // Back up the existing subscriptions
        if (!$schemaManager->tablesExist(['tl_newsletter_recipients_backup'])) {
            $this->connection->executeStatement('
                CREATE TABLE
                    tl_newsletter_recipients_backup
                LIKE
                    tl_newsletter_recipients
            ');

            $this->connection->executeStatement('
                INSERT
                    tl_newsletter_recipients_backup
                SELECT
                    *
                FROM
                    tl_newsletter_recipients
            ');
        }

        // Find multiple subscriptions for the same channel with the same e-mail address
        $duplicates = $this->connection->fetchAllAssociative('
            SELECT
                pid, email
            FROM
                tl_newsletter_recipients
            GROUP BY
                pid, email
            HAVING
                COUNT(*) > 1
            ORDER BY
                pid
        ');

        $messages = [];

        foreach ($duplicates as $duplicate) {
            $count = 0;

            // Find the oldest, active subscription preferring real subscriptions over imported ones
            $subscriptions = $this->connection->fetchAllAssociative(
                "
                    SELECT *
                      FROM tl_newsletter_recipients
                     WHERE pid = :pid
                       AND email = :email
                     ORDER BY active = '1' DESC, addedOn != '' DESC, id
                ",
                ['pid' => $duplicate['pid'], 'email' => $duplicate['email']]
            );

            foreach ($subscriptions as $subscription) {
                if (0 === $count++) {
                    continue; // keep the first subscription
                }

                $this->connection->executeStatement(
                    'DELETE FROM tl_newsletter_recipients WHERE id = :id',
                    ['id' => $subscription['id']]
                );
            }

            $messages[] = $duplicate['email'];
        }

        $this->connection->executeStatement('CREATE UNIQUE INDEX pid_email ON tl_newsletter_recipients (pid, email)');

        if ($messages) {
            return $this->createResult(
                true,
                $this->translator->trans('duplicate_subscriptions')."\n\n * ".implode("\n * ", $messages)."\n\n".$this->translator->trans('duplicates_purged')
            );
        }

        return $this->createResult(true);
    }
}
