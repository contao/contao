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
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var TranslatorInterface
     */
    private $translator;

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
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_newsletter_recipients'])) {
            return false;
        }

        $columns = $schemaManager->listTableIndexes('tl_newsletter_recipients');

        return !isset($columns['pid_email']);
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->connection->getSchemaManager();

        // Back up the existing subscriptions
        if (!$schemaManager->tablesExist(['tl_newsletter_recipients_backup'])) {
            $this->connection->query('
                CREATE TABLE
                    tl_newsletter_recipients_backup
                LIKE
                    tl_newsletter_recipients
            ');

            $this->connection->query('
                INSERT
                    tl_newsletter_recipients_backup
                SELECT
                    *
                FROM
                    tl_newsletter_recipients
            ');
        }

        // Find multiple subscriptions for the same channel with the same e-mail address
        $duplicates = $this->connection->query('
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

        while (false !== ($duplicate = $duplicates->fetch(\PDO::FETCH_OBJ))) {
            $count = 0;

            // Find the oldest, active subscription preferring real subscriptions over imported ones
            $subscriptions = $this->connection->prepare("
                SELECT
                    *
                FROM
                    tl_newsletter_recipients
                WHERE
                    pid = :pid AND email = :email
                ORDER BY
                    active = '1' DESC, addedOn != '' DESC, id
            ");

            $subscriptions->execute(['pid' => $duplicate->pid, ':email' => $duplicate->email]);

            while (false !== ($subscription = $subscriptions->fetch(\PDO::FETCH_OBJ))) {
                if (0 === $count++) {
                    continue; // keep the first subscription
                }

                $delete = $this->connection->prepare('
                    DELETE FROM
                        tl_newsletter_recipients
                    WHERE
                        id = :id
                ');

                $delete->execute(['id' => $subscription->id]);
            }

            $messages[] = $duplicate->email;
        }

        $this->connection->query('CREATE UNIQUE INDEX pid_email ON tl_newsletter_recipients (pid, email)');

        if ($messages) {
            return $this->createResult(
                true,
                $this->translator->trans('duplicate_subscriptions')."\n\n * ".implode("\n * ", $messages)."\n\n".$this->translator->trans('duplicates_purged')
            );
        }

        return $this->createResult(true);
    }
}
