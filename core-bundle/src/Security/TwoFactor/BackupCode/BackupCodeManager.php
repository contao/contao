<?php

declare(strict_types=1);

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
}
