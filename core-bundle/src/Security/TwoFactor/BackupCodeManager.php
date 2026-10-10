<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\TwoFactor;

use Contao\User;
use Doctrine\DBAL\Connection;
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;

class BackupCodeManager implements BackupCodeManagerInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function isBackupCode(object $user, string $code): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        if (null === $user->backupCodes) {
            return false;
        }

        try {
            $backupCodes = json_decode($user->backupCodes, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return false;
        }

        foreach ($backupCodes as $backupCode) {
            if (password_verify($code, $backupCode)) {
                return true;
            }
        }

        return false;
    }

    public function invalidateBackupCode(object $user, string $code): void
    {
        if (!$user instanceof User) {
            return;
        }

        $codeToInvalidate = false;

        try {
            $backupCodes = array_values(json_decode($user->backupCodes, true, 512, JSON_THROW_ON_ERROR));
        } catch (\JsonException) {
            return;
        }

        foreach ($backupCodes as $backupCode) {
            if (password_verify($code, $backupCode)) {
                $codeToInvalidate = $backupCode;
                break;
            }
        }

        if (false === $codeToInvalidate) {
            return;
        }

        $key = array_search($codeToInvalidate, $backupCodes, true);

        if (false === $key) {
            return;
        }

        unset($backupCodes[$key]);

        $this->saveUser($user, $backupCodes);
    }

    public function generateBackupCodes(User $user): array
    {
        $backupCodes = [];

        for ($i = 0; $i < 10; ++$i) {
            $backupCodes[] = $this->generateCode();
        }

        // Only run password_hash when saving to the database, the method needs to return
        // the raw values.
        $this->saveUser($user, array_map(
            static fn ($backupCode) => password_hash($backupCode, PASSWORD_DEFAULT),
            $backupCodes,
        ));

        return $backupCodes;
    }

    private function generateCode(): string
    {
        return bin2hex(random_bytes(3)).'-'.bin2hex(random_bytes(3));
    }

    private function saveUser(User $user, array $backupCodes): void
    {
        $this->connection->update(
            $user->getTable(),
            ['backupCodes' => json_encode(array_values($backupCodes), JSON_THROW_ON_ERROR)],
            ['id' => $user->id],
        );
    }
}
