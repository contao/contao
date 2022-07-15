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
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;

class BackupCodeManager implements BackupCodeManagerInterface
{
    /**
     * @param mixed $user
     */
    public function isBackupCode($user, string $code): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        if (null === $user->backupCodes) {
            return false;
        }

        $backupCodes = json_decode($user->backupCodes, true);

        if (null === $backupCodes) {
            return false;
        }

        foreach ($backupCodes as $backupCode) {
            if (password_verify($code, $backupCode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $user
     */
    public function invalidateBackupCode($user, string $code): void
    {
        if (!$user instanceof User) {
            return;
        }

        $codeToInvalidate = false;
        $backupCodes = array_values(json_decode($user->backupCodes, true));

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

        $user->backupCodes = json_encode(array_values($backupCodes));
        $user->save();
    }

    public function generateBackupCodes(User $user): array
    {
        $backupCodes = [];

        for ($i = 0; $i < 10; ++$i) {
            $backupCodes[] = $this->generateCode();
        }

        $user->backupCodes = json_encode(
            array_map(
                static fn ($backupCode) => password_hash($backupCode, PASSWORD_DEFAULT),
                $backupCodes
            ),
            JSON_THROW_ON_ERROR
        );

        $user->save();

        return $backupCodes;
    }

    private function generateCode(): string
    {
        return bin2hex(random_bytes(3)).'-'.bin2hex(random_bytes(3));
    }
}
