<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Database;

class Version447Update extends AbstractVersionUpdate
{
    /**
     * {@inheritdoc}
     */
    public function shouldBeRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_newsletter_recipients'])) {
            return false;
        }

        $columns = $schemaManager->listTableIndexes('tl_newsletter_recipients');

        return !isset($columns['pid_email']);
    }

    /**
     * {@inheritdoc}
     */
    public function run(): void
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

            $this->addMessage(sprintf('<li>%s</li>', $duplicate->email));
        }

        if ($this->hasMessage()) {
            $translator = $this->container->get('translator');

            $this->prependMessage(sprintf('<p>%s</p><ul>', $translator->trans('duplicate_subscriptions')));
            $this->addMessage(sprintf('</ul><p>%s</p>', $translator->trans('duplicates_purged')));
        }
    }
}
