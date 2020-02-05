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
    public function isBackupCode($user, string $code): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        return \in_array($code, json_decode($user->backupCodes, true), true);
    }

    public function invalidateBackupCode($user, string $code): void
    {
        if (!$user instanceof User) {
            return;
        }

        $backupCodes = json_decode($user->backupCodes, true);
        $key = array_search($code, $backupCodes, true);

        if (false !== $key) {
            unset($backupCodes[$key]);
            $user->backupCodes = json_encode($backupCodes);
        }

        $user->save();
    }

    public function generateBackupCodes(User $user): ?array
    {
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
        return bin2hex(random_bytes(3)).'-'.bin2hex(random_bytes(3));
    }
}
