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
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\User\UserInterface;

class BackupCodeManagerTest extends TestCase
{
    public function testDoesNotHandleNonContaoUsers(): void
    {
        $backupCodeManager = new BackupCodeManager($this->createStub(Connection::class));
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

        $backupCodeManager = new BackupCodeManager($this->createStub(Connection::class));

        $this->assertFalse($backupCodeManager->isBackupCode($frontendUser, '123456'));
        $this->assertFalse($backupCodeManager->isBackupCode($backendUser, '234567'));
    }

    public function testHandlesInvalidJson(): void
    {
        $frontendUser = $this->createClassWithPropertiesStub(FrontendUser::class);
        $frontendUser->backupCodes = 'foobar';

        $backendUser = $this->createClassWithPropertiesStub(BackendUser::class);
        $backendUser->backupCodes = 'foobar';

        $backupCodeManager = new BackupCodeManager($this->createStub(Connection::class));

        $this->assertFalse($backupCodeManager->isBackupCode($frontendUser, '123456'));
        $this->assertFalse($backupCodeManager->isBackupCode($backendUser, '234567'));
    }

    public function testHandlesContaoUsers(): void
    {
        $backupCodes = json_encode(
            [
                password_hash('123456', PASSWORD_DEFAULT),
                password_hash('234567', PASSWORD_DEFAULT),
            ],
            JSON_THROW_ON_ERROR,
        );

        $frontendUser = $this->createClassWithPropertiesStub(FrontendUser::class);
        $frontendUser->backupCodes = $backupCodes;

        $backendUser = $this->createClassWithPropertiesStub(BackendUser::class);
        $backendUser->backupCodes = $backupCodes;

        $backupCodeManager = new BackupCodeManager($this->createStub(Connection::class));

        $this->assertTrue($backupCodeManager->isBackupCode($frontendUser, '123456'));
        $this->assertTrue($backupCodeManager->isBackupCode($backendUser, '234567'));
    }

    public function testInvalidatesBackupCode(): void
    {
        $backupCodes = json_encode(
            [
                '$2y$10$vY0fVrqfUmzzHSQpT6ZMPOGwrYLq.9s/Y1M9cV9/0K0SlGH/kMotC', // 4ead45-4ea70a
                '$2y$10$Ie2VHgQLiNTfAI1kDV19U.i9dsvIE4tt3h75rpVHnoWqJFS0Lq1Yy', // 0082ec-b95f03
            ],
            JSON_THROW_ON_ERROR,
        );

        $user = $this->createClassWithPropertiesStub(BackendUser::class);
        $user
            ->method('getTable')
            ->willReturn('tl_user')
        ;
        $user->id = 1;
        $user->backupCodes = $backupCodes;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('update')
            ->with(
                'tl_user',
                $this->callback(
                    static function (array $data): bool {
                        $codes = json_decode($data['backupCodes'], true);

                        return 1 === \count($codes) && '$2y$10$Ie2VHgQLiNTfAI1kDV19U.i9dsvIE4tt3h75rpVHnoWqJFS0Lq1Yy' === $codes[0];
                    },
                ),
                ['id' => 1],
            )
        ;

        $backupCodeManager = new BackupCodeManager($connection);
        $backupCodeManager->invalidateBackupCode($user, '4ead45-4ea70a');
    }

    public function testGenerateBackupCodes(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class);
        $user
            ->method('getTable')
            ->willReturn('tl_user')
        ;
        $user->id = 1;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('update')
            ->with(
                'tl_user',
                $this->callback(
                    static function (array $data): bool {
                        $codes = json_decode($data['backupCodes'], true);

                        return 10 === \count($codes);
                    },
                ),
                ['id' => 1],
            )
        ;

        $backupCodeManager = new BackupCodeManager($connection);
        $backupCodes = $backupCodeManager->generateBackupCodes($user);

        $this->assertCount(10, $backupCodes);
        $this->assertMatchesRegularExpression('/[a-f0-9]{6}-[a-f0-9]{6}/', $backupCodes[0]);
    }
}
