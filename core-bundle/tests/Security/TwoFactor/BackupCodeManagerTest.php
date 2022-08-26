<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\TwoFactor;

use Contao\BackendUser;
use Contao\CoreBundle\Security\TwoFactor\BackupCodeManager;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\User\UserInterface;

class BackupCodeManagerTest extends TestCase
{
    public function testDoesNotHandleNonContaoUsers(): void
    {
        $backupCodeManager = new BackupCodeManager();
        $user = $this->createMock(UserInterface::class);

        $this->assertFalse($backupCodeManager->isBackupCode($user, '123456'));

        $backupCodeManager->invalidateBackupCode($user, '123456');
    }

    public function testHandlesNullValue(): void
    {
        $frontendUser = $this->mockClassWithProperties(FrontendUser::class, []);
        $frontendUser->backupCodes = null;

        $backendUser = $this->mockClassWithProperties(BackendUser::class);
        $backendUser->backupCodes = null;

        $backupCodeManager = new BackupCodeManager();

        $this->assertFalse($backupCodeManager->isBackupCode($frontendUser, '123456'));
        $this->assertFalse($backupCodeManager->isBackupCode($backendUser, '234567'));
    }

    public function testHandlesInvalidJson(): void
    {
        $frontendUser = $this->mockClassWithProperties(FrontendUser::class, []);
        $frontendUser->backupCodes = 'foobar';

        $backendUser = $this->mockClassWithProperties(BackendUser::class);
        $backendUser->backupCodes = 'foobar';

        $backupCodeManager = new BackupCodeManager();

        $this->assertFalse($backupCodeManager->isBackupCode($frontendUser, '123456'));
        $this->assertFalse($backupCodeManager->isBackupCode($backendUser, '234567'));
    }

    public function testHandlesContaoUsers(): void
    {
        $backupCodes = json_encode([
            password_hash('123456', PASSWORD_DEFAULT),
            password_hash('234567', PASSWORD_DEFAULT),
        ]);

        $frontendUser = $this->mockClassWithProperties(FrontendUser::class, []);
        $frontendUser->backupCodes = $backupCodes;

        $backendUser = $this->mockClassWithProperties(BackendUser::class);
        $backendUser->backupCodes = $backupCodes;

        $backupCodeManager = new BackupCodeManager();

        $this->assertTrue($backupCodeManager->isBackupCode($frontendUser, '123456'));
        $this->assertTrue($backupCodeManager->isBackupCode($backendUser, '234567'));
    }

    public function testInvalidatesBackupCode(): void
    {
        $backupCodes = json_encode([
            '$2y$10$vY0fVrqfUmzzHSQpT6ZMPOGwrYLq.9s/Y1M9cV9/0K0SlGH/kMotC', // 4ead45-4ea70a
            '$2y$10$Ie2VHgQLiNTfAI1kDV19U.i9dsvIE4tt3h75rpVHnoWqJFS0Lq1Yy', // 0082ec-b95f03
        ]);

        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->backupCodes = $backupCodes;

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $backupCodeManager = new BackupCodeManager();
        $backupCodeManager->invalidateBackupCode($user, '4ead45-4ea70a');

        $this->assertFalse($backupCodeManager->isBackupCode($user, '4ead45-4ea70a'));
        $this->assertTrue($backupCodeManager->isBackupCode($user, '0082ec-b95f03'));
    }

    public function testGenerateBackupCodes(): void
    {
        $backupCodeManager = new BackupCodeManager();

        $user = $this->mockClassWithProperties(BackendUser::class);
        $user
            ->expects($this->once())
            ->method('save')
        ;

        $backupCodes = $backupCodeManager->generateBackupCodes($user);

        $this->assertCount(10, $backupCodes);
        $this->assertCount(10, json_decode($user->backupCodes, true));
        $this->assertMatchesRegularExpression('/[a-f0-9]{6}-[a-f0-9]{6}/', $backupCodes[0]);
    }
}
