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
        $user = $this->createStub(UserInterface::class);

        $this->assertFalse($backupCodeManager->isBackupCode($user, '123456'));

        $backupCodeManager->invalidateBackupCode($user, '123456');
    }

    public function testHandlesNullValue(): void
    {
        $frontendUser = $this->createClassWithPropertiesStub(FrontendUser::class);
        $frontendUser->backupCodes = null;

        $backendUser = $this->createClassWithPropertiesStub(BackendUser::class);
        $backendUser->backupCodes = null;

        $backupCodeManager = new BackupCodeManager();

        $this->assertFalse($backupCodeManager->isBackupCode($frontendUser, '123456'));
        $this->assertFalse($backupCodeManager->isBackupCode($backendUser, '234567'));
    }

    public function testHandlesInvalidJson(): void
    {
        $frontendUser = $this->createClassWithPropertiesStub(FrontendUser::class);
        $frontendUser->backupCodes = 'foobar';

        $backendUser = $this->createClassWithPropertiesStub(BackendUser::class);
        $backendUser->backupCodes = 'foobar';

        $backupCodeManager = new BackupCodeManager();

        $this->assertFalse($backupCodeManager->isBackupCode($frontendUser, '123456'));
        $this->assertFalse($backupCodeManager->isBackupCode($backendUser, '234567'));
    }

    public function testHandlesContaoUsers(): void
    {
        $backupCodes = json_encode(
            [
                password_hash('123456', PASSWORD_BCRYPT, ['cost' => 4]),
                password_hash('234567', PASSWORD_BCRYPT, ['cost' => 4]),
            ],
            JSON_THROW_ON_ERROR,
        );

        $frontendUser = $this->createClassWithPropertiesStub(FrontendUser::class);
        $frontendUser->backupCodes = $backupCodes;

        $backendUser = $this->createClassWithPropertiesStub(BackendUser::class);
        $backendUser->backupCodes = $backupCodes;

        $backupCodeManager = new BackupCodeManager();

        $this->assertTrue($backupCodeManager->isBackupCode($frontendUser, '123456'));
        $this->assertTrue($backupCodeManager->isBackupCode($backendUser, '234567'));
    }

    public function testInvalidatesBackupCode(): void
    {
        $backupCodes = json_encode(
            [
                '$2y$04$fHlPcz85jpnvCsgNzGRRauZqAoLHE34Ye5RCxoEoQF6T04uuzQCiW', // 4ead45-4ea70a
                '$2y$04$77eT1qrNoPVw7NZedUe3xuGgPDrxgAETi0jvq46yq2eUHMMfQ34Ei', // 0082ec-b95f03
            ],
            JSON_THROW_ON_ERROR,
        );

        $user = $this->createClassWithPropertiesMock(BackendUser::class);
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
        $backupCodeManager = new BackupCodeManager(['cost' => 4]);

        $user = $this->createClassWithPropertiesMock(BackendUser::class);
        $user
            ->expects($this->once())
            ->method('save')
        ;

        $backupCodes = $backupCodeManager->generateBackupCodes($user);

        $this->assertCount(10, $backupCodes);

        $hashedBackupCodes = json_decode($user->backupCodes, true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(10, $hashedBackupCodes);
        $this->assertStringStartsWith('$2y$04$', $hashedBackupCodes[0]);
        $this->assertMatchesRegularExpression('/[a-f0-9]{6}-[a-f0-9]{6}/', $backupCodes[0]);
    }
}
