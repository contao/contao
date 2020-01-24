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
use PHPUnit\Framework\MockObject\MockObject;
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

    public function testHandlesContaoUsers(): void
    {
        $backupCodes = json_encode(['123456', '234567']);

        /** @var FrontendUser&MockObject $frontendUser */
        $frontendUser = $this->mockClassWithProperties(FrontendUser::class, []);
        $frontendUser->backupCodes = $backupCodes;

        /** @var BackendUser&MockObject $backendUser */
        $backendUser = $this->mockClassWithProperties(BackendUser::class);
        $backendUser->backupCodes = $backupCodes;

        $backupCodeManager = new BackupCodeManager();

        $this->assertTrue($backupCodeManager->isBackupCode($frontendUser, '123456'));
        $this->assertTrue($backupCodeManager->isBackupCode($backendUser, '234567'));
    }

    public function testInvalidatesBackupCode(): void
    {
        $backupCodes = json_encode(['123456', '234567']);

        /** @var BackendUser&MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->backupCodes = $backupCodes;

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $backupCodeManager = new BackupCodeManager();
        $backupCodeManager->invalidateBackupCode($user, '123456');

        $this->assertFalse($backupCodeManager->isBackupCode($user, '123456'));
    }

    public function testGenerateBackupCodes(): void
    {
        $backupCodeManager = new BackupCodeManager();

        /** @var BackendUser&MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user
            ->expects($this->once())
            ->method('save')
        ;

        $backupCodes = $backupCodeManager->generateBackupCodes($user);

        $this->assertCount(10, $backupCodes);
        $this->assertCount(10, json_decode($user->backupCodes, true));
        $this->assertRegExp('/[a-f0-9]{6}-[a-f0-9]{6}/', $backupCodes[0]);
    }
}
