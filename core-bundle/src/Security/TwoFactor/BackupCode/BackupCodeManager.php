<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\TwoFactor\BackupCode;

use Contao\User;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;

class BackupCodeManager implements BackupCodeManagerInterface
{
    public function isBackupCode($user, string $code): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        if ($user instanceof BackupCodeInterface) {
            return $user->isBackupCode($code);
        }

        return false;
    }

    public function invalidateBackupCode($user, string $code): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user instanceof BackupCodeInterface) {
            $user->invalidateBackupCode($code);
            $user->save();
        }
    }

    public function generateBackupCodes(User $user): ?array
    {
        if (!$user instanceof User) {
            return null;
        }

        if (!$user instanceof BackupCodeInterface) {
            return null;
        }

        $backupCodes = [];

        for ($i = 0; $i < 10; ++$i) {
            $backupCodes[] = $this->generateCode();
        }

        $user->backupCodes = json_encode($backupCodes);
        $user->save();

        return $backupCodes;
    }

    private function generateCode(): string
    {
        return sprintf(
            '%s-%s',
            substr(uniqid(bin2hex(random_bytes(128)), true), 0, 5),
            substr(uniqid(bin2hex(random_bytes(128)), true), 0, 5)
        );
    }
}
