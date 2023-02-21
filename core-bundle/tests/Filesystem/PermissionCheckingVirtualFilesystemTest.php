<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Filesystem\PermissionCheckingVirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PermissionCheckingVirtualFilesystemTest extends TestCase
{
    /**
     * @dataProvider provideOperationsThatShouldBeDenied
     */
    public function testDeniesAccess(string $operation, array $arguments, string $permission, string $exception): void
    {
        $virtualFilesystem = $this->createMock(VirtualFilesystemInterface::class);

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->method('isGranted')
            ->willReturnCallback(
                static function (string $attribute) use ($permission): bool {
                    if ($attribute === $permission) {
                        return false;
                    }

                    return true;
                }
            )
        ;

        $container = new Container();
        $container->set('security.authorization_checker', $authorizationChecker);

        $customViewVirtualFilesystem = new PermissionCheckingVirtualFilesystem(
            $virtualFilesystem,
            new Security($container)
        );

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage($exception);

        $customViewVirtualFilesystem->$operation(...$arguments);
    }

    public function provideOperationsThatShouldBeDenied(): \Generator
    {
        $resource = tmpfile();
        fclose($resource);

        yield 'write' => [
            'write',
            ['foo', ''],
            ContaoCorePermissions::USER_CAN_UPLOAD_FILES,
            'Access denied to upload files at location "foo".',
        ];

        yield 'write stream' => [
            'writeStream',
            ['foo', $resource],
            ContaoCorePermissions::USER_CAN_UPLOAD_FILES,
            'Access denied to upload files at location "foo".',
        ];

        yield 'delete' => [
            'delete',
            ['foo'],
            ContaoCorePermissions::USER_CAN_DELETE_FILE,
            'Access denied to delete file at location "foo".',
        ];

        yield 'delete directory' => [
            'deleteDirectory',
            ['foo'],
            ContaoCorePermissions::USER_CAN_DELETE_RECURSIVELY,
            'Access denied to delete recursively at location "foo".',
        ];

        yield 'create directory' => [
            'createDirectory',
            ['foo'],
            ContaoCorePermissions::USER_CAN_UPLOAD_FILES,
            'Access denied to upload files at location "foo".',
        ];

        yield 'copy' => [
            'copy',
            ['foo', 'bar'],
            ContaoCorePermissions::USER_CAN_UPLOAD_FILES,
            'Access denied to upload files at location "bar".',
        ];

        yield 'move without being able to delete' => [
            'move',
            ['foo', 'bar'],
            ContaoCorePermissions::USER_CAN_DELETE_FILE,
            'Access denied to delete file at location "foo".',
        ];

        yield 'move without being able to create' => [
            'move',
            ['foo', 'bar'],
            ContaoCorePermissions::USER_CAN_UPLOAD_FILES,
            'Access denied to upload files at location "bar".',
        ];
    }
}
