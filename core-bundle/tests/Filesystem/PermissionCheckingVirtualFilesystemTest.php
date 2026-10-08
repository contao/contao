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
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PermissionCheckingVirtualFilesystemTest extends TestCase
{
    #[DataProvider('provideOperationsThatShouldBeDenied')]
    public function testDeniesAccess(string $operation, array $arguments, array|string $permissionToDeny, string $exception): void
    {
        $filesStorage = $this->createStub(VirtualFilesystem::class);
        $filesStorage
            ->method('getPrefix')
            ->willReturn('files')
        ;

        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->method('isGranted')
            ->willReturnCallback(
                static function (string $attribute, mixed $subject) use ($permissionToDeny): bool {
                    $permissionToDeny = (array) $permissionToDeny;

                    if ($attribute !== $permissionToDeny[0]) {
                        return true;
                    }

                    return null !== ($permissionToDeny[1] ?? null) && $subject !== $permissionToDeny[1];
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
            'read',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/foo'],
            'Access denied to access path at location "foo".',
        ];

        yield 'readStream' => [
            'readStream',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/foo'],
            'Access denied to access path at location "foo".',
        ];

        yield 'write without subpath access' => [
            'write',
            ['foo', ''],
            [ContaoCorePermissions::USER_CAN_ACCESS_SUBPATH, 'files/foo'],
            'Access denied to access subpath at location "foo".',
        ];

        yield 'write' => [
            'write',
            ['foo', ''],
            ContaoCorePermissions::USER_CAN_UPLOAD_FILES,
            'Access denied to upload files.',
        ];

        yield 'write stream without subpath access' => [
            'writeStream',
            ['foo', $resource],
            [ContaoCorePermissions::USER_CAN_ACCESS_SUBPATH, 'files/foo'],
            'Access denied to access subpath at location "foo".',
        ];

        yield 'write stream' => [
            'writeStream',
            ['foo', $resource],
            ContaoCorePermissions::USER_CAN_UPLOAD_FILES,
            'Access denied to upload files.',
        ];

        yield 'delete without subpath access' => [
            'delete',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_SUBPATH, 'files/foo'],
            'Access denied to access subpath at location "foo".',
        ];

        yield 'delete' => [
            'delete',
            ['foo'],
            ContaoCorePermissions::USER_CAN_DELETE_FILE,
            'Access denied to delete file.',
        ];

        yield 'delete directory without subpath access' => [
            'deleteDirectory',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_SUBPATH, 'files/foo'],
            'Access denied to access subpath at location "foo".',
        ];

        yield 'delete directory' => [
            'deleteDirectory',
            ['foo'],
            ContaoCorePermissions::USER_CAN_DELETE_RECURSIVELY,
            'Access denied to delete recursively.',
        ];

        yield 'create directory without subpath access' => [
            'createDirectory',
            ['foo'],
            [ContaoCorePermissions::USER_CAN_ACCESS_SUBPATH, 'files/foo'],
            'Access denied to access subpath at location "foo".',
        ];

        yield 'create directory' => [
            'createDirectory',
            ['foo'],
            ContaoCorePermissions::USER_CAN_UPLOAD_FILES,
            'Access denied to upload files.',
        ];

        yield 'copy without path access' => [
            'copy',
            ['foo', 'bar'],
            [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/bar'],
            'Access denied to access path at location "bar".',
        ];

        yield 'copy' => [
            'copy',
            ['foo', 'bar'],
            ContaoCorePermissions::USER_CAN_UPLOAD_FILES,
            'Access denied to upload files.',
        ];

        yield 'move without being able to access source' => [
            'move',
            ['foo', 'bar'],
            [ContaoCorePermissions::USER_CAN_ACCESS_SUBPATH, 'files/foo'],
            'Access denied to access subpath at location "foo".',
        ];

        yield 'move without being able to access destination' => [
            'move',
            ['foo', 'bar'],
            [ContaoCorePermissions::USER_CAN_ACCESS_SUBPATH, 'files/bar'],
            'Access denied to access subpath at location "bar".',
        ];

        yield 'move without being able to rename' => [
            'move',
            ['foo', 'bar'],
            ContaoCorePermissions::USER_CAN_RENAME_FILE,
            'Access denied to rename file.',
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
            'getExtraMetadata',
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

    #[DataProvider('provideInvalidPaths')]
    public function testDisallowsAccessForInvalidPaths(string $invalidPath, string $expectedMessage): void
    {
        $permissionCheckingVirtualFilesystem = new PermissionCheckingVirtualFilesystem(
            $this->createStub(VirtualFilesystem::class),
            $this->createStub(Security::class),
        );

        $this->assertFalse($permissionCheckingVirtualFilesystem->canAccessLocation($invalidPath));

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

    public function testChecksPermissionForSpecificUser(): void
    {
        $user = $this->createStub(UserInterface::class);

        $filesStorage = $this->createStub(VirtualFilesystem::class);
        $filesStorage
            ->method('getPrefix')
            ->willReturn('files')
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGrantedForUser')
            ->with($user, ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'files/foo')
            ->willReturn(true)
        ;

        $permissionCheckingVirtualFilesystem = new PermissionCheckingVirtualFilesystem(
            $filesStorage,
            $security,
            $user,
        );

        $this->assertTrue($permissionCheckingVirtualFilesystem->canAccessLocation('foo'));
    }
}
