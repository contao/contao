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
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\CoreBundle\Filesystem\PermissionCheckingVirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
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
    public function testDeniesAccess(string $operation, array $arguments, array|string $permissionToDeny, string $exception): void
    {
        $filesStorage = $this->createMock(VirtualFilesystem::class);
        $filesStorage
            ->method('getPrefix')
            ->willReturn('files')
        ;

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->method('isGranted')
            ->willReturnCallback(
                function (string $attribute, mixed $subject) use ($permissionToDeny): bool {
                    $permissionToDeny = (array) $permissionToDeny;

                    if ($attribute !== $permissionToDeny[0]) {
                        return true;
                    }

                    if (null !== ($permissionToDeny[1] ?? null)) {
                        $this->assertSame($permissionToDeny[1], $subject, 'wrong subject');
                    }

                    return false;
                },
            )
        ;

        $container = new Container();
        $container->set('security.authorization_checker', $authorizationChecker);

        $permissionCheckingVirtualFilesystem = new PermissionCheckingVirtualFilesystem(
            $filesStorage,
            new Security($container),
        );

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage($exception);

        $permissionCheckingVirtualFilesystem->$operation(...$arguments);
    }

    public static function provideOperationsThatShouldBeDenied(): iterable
    {
        $resource = tmpfile();
        fclose($resource);

        yield 'has' => [
            'has',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/foo'],
            'Access denied to access path at location "foo".',
        ];

        yield 'has with un-normalized path' => [
            'has',
            ['foo/../bar'],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/bar'],
            'Access denied to access path at location "foo/../bar".',
        ];

        yield 'fileExists' => [
            'fileExists',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/foo'],
            'Access denied to access path at location "foo".',
        ];

        yield 'directoryExists' => [
            'directoryExists',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/foo'],
            'Access denied to access path at location "foo".',
        ];

        yield 'read' => [
            'fileExists',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/foo'],
            'Access denied to access path at location "foo".',
        ];

        yield 'readStream' => [
            'directoryExists',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/foo'],
            'Access denied to access path at location "foo".',
        ];

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

        yield 'get' => [
            'get',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/foo'],
            'Access denied to access path at location "foo".',
        ];

        yield 'listContents' => [
            'listContents',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/foo'],
            'Access denied to access path at location "foo".',
        ];

        yield 'getLastModified' => [
            'getLastModified',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/foo'],
            'Access denied to access path at location "foo".',
        ];

        yield 'getFileSize' => [
            'getFileSize',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/foo'],
            'Access denied to access path at location "foo".',
        ];

        yield 'getMimeType' => [
            'getMimeType',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/foo'],
            'Access denied to access path at location "foo".',
        ];

        yield 'getExtraMetadata' => [
            'getMimeType',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/foo'],
            'Access denied to access path at location "foo".',
        ];

        yield 'setExtraMetadata' => [
            'setExtraMetadata',
            ['foo', new ExtraMetadata()],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/foo'],
            'Access denied to access path at location "foo".',
        ];

        yield 'generatePublicUri' => [
            'generatePublicUri',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/foo'],
            'Access denied to access path at location "foo".',
        ];
    }

    /**
     * @dataProvider provideInvalidPaths
     */
    public function testDisallowsAccessForInvalidPaths(string $invalidPath, string $expectedMessage): void
    {
        $permissionCheckingVirtualFilesystem = new PermissionCheckingVirtualFilesystem(
            $this->createMock(VirtualFilesystem::class),
            $this->createMock(Security::class),
        );

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage($expectedMessage);

        $permissionCheckingVirtualFilesystem->has($invalidPath);
    }

    public static function provideInvalidPaths(): iterable
    {
        yield 'relative path escaping boundary' => [
            '../foo',
            'Access denied to access path at location "../foo".',
        ];

        yield 'local path escaping boundary' => [
            './../',
            'Access denied to access path at location "./../".',
        ];

        yield 'absolute path' => [
            '/absolute/foo',
            'Access denied to access path at location "/absolute/foo".',
        ];
    }
}
