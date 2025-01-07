<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Filesystem\PublicUri\OptionsInterface;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Psr\Http\Message\UriInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Uid\Uuid;

/**
 * This decorator adds security checks to all methods accessing resources and can
 * be used with any Contao\CoreBundle\Filesystem\VirtualFilesystem implementation.
 *
 * @internal
 */
class PermissionCheckingVirtualFilesystem implements VirtualFilesystemInterface
{
    use VirtualFilesystemDecoratorTrait;

    public function __construct(
        VirtualFilesystem $virtualFilesystem,
        private readonly Security $security,
    ) {
        $this->inner = $virtualFilesystem;
    }

    public function has(Uuid|string $location, int $accessFlags = VirtualFilesystemInterface::NONE): bool
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_PATH, $location);

        return $this->inner->has($location, $accessFlags);
    }

    public function fileExists(Uuid|string $location, int $accessFlags = VirtualFilesystemInterface::NONE): bool
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_PATH, $location);

        return $this->inner->fileExists($location, $accessFlags);
    }

    public function directoryExists(Uuid|string $location, int $accessFlags = VirtualFilesystemInterface::NONE): bool
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_PATH, $location);

        return $this->inner->directoryExists($location, $accessFlags);
    }

    public function read(Uuid|string $location): string
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_PATH, $location);

        return $this->inner->read($location);
    }

    public function readStream(Uuid|string $location)
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_PATH, $location);

        return $this->inner->readStream($location);
    }

    public function write(Uuid|string $location, string $contents, array $options = []): void
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_UPLOAD_FILES, $location);

        $this->inner->write($location, $contents, $options);
    }

    public function writeStream(Uuid|string $location, $contents, array $options = []): void
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_UPLOAD_FILES, $location);

        $this->inner->writeStream($location, $contents, $options);
    }

    public function delete(Uuid|string $location): void
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_DELETE_FILE, $location);

        $this->inner->delete($location);
    }

    public function deleteDirectory(Uuid|string $location): void
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_DELETE_RECURSIVELY, $location);

        $this->inner->deleteDirectory($location);
    }

    public function createDirectory(Uuid|string $location, array $options = []): void
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_UPLOAD_FILES, $location);

        $this->inner->createDirectory($location, $options);
    }

    public function copy(Uuid|string $source, string $destination, array $options = []): void
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_UPLOAD_FILES, $destination);

        $this->inner->copy($source, $destination, $options);
    }

    public function move(Uuid|string $source, string $destination, array $options = []): void
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_DELETE_FILE, $source);
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_UPLOAD_FILES, $destination);

        $this->inner->move($source, $destination, $options);
    }

    public function get(Uuid|string $location, int $accessFlags = VirtualFilesystemInterface::NONE): FilesystemItem|null
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_PATH, $location);

        return $this->inner->get($location, $accessFlags);
    }

    public function listContents(Uuid|string $location, bool $deep = false, int $accessFlags = VirtualFilesystemInterface::NONE): FilesystemItemIterator
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_PATH, $location);

        return $this->inner->listContents($location, $deep, $accessFlags);
    }

    public function getLastModified(Uuid|string $location, int $accessFlags = VirtualFilesystemInterface::NONE): int
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_PATH, $location);

        return $this->inner->getLastModified($location, $accessFlags);
    }

    public function getFileSize(Uuid|string $location, int $accessFlags = VirtualFilesystemInterface::NONE): int
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_PATH, $location);

        return $this->inner->getFileSize($location, $accessFlags);
    }

    public function getMimeType(Uuid|string $location, int $accessFlags = VirtualFilesystemInterface::NONE): string
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_PATH, $location);

        return $this->inner->getMimeType($location, $accessFlags);
    }

    public function getExtraMetadata(Uuid|string $location, int $accessFlags = VirtualFilesystemInterface::NONE): ExtraMetadata
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_PATH, $location);

        return $this->inner->getExtraMetadata($location, $accessFlags);
    }

    public function setExtraMetadata(Uuid|string $location, ExtraMetadata $metadata): void
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_PATH, $location);

        $this->inner->setExtraMetadata($location, $metadata);
    }

    public function generatePublicUri(Uuid|string $location, OptionsInterface|null $options = null): UriInterface|null
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_PATH, $location);

        return $this->inner->generatePublicUri($location, $options);
    }

    private function denyAccessUnlessGranted(string $attribute, Uuid|string $location): void
    {
        if ($this->canAccess($attribute, $location)) {
            return;
        }

        $permission = array_flip((new \ReflectionClass(ContaoCorePermissions::class))->getConstants())[$attribute];
        $action = strtolower(str_replace('_', ' ', substr($permission, 9)));

        $exception = new AccessDeniedException(\sprintf('Access denied to %s at location "%s".', $action, $location));
        $exception->setAttributes($attribute);
        $exception->setSubject($location);

        throw $exception;
    }

    private function canAccess(string $attribute, Uuid|string $location): bool
    {
        if (!$this->inner instanceof VirtualFilesystem) {
            return false;
        }

        $path = $location instanceof Uuid
            ? Path::canonicalize($this->inner->resolveUuid($location))
            : Path::canonicalize($location);

        // Deny access for resources where we cannot generate a meaningful root storage
        // relative path.
        if (Path::isAbsolute($path) || str_starts_with($path, '..')) {
            return false;
        }

        $rootStorageRelativePath = Path::join($this->inner->getPrefix(), $path);

        return $this->security->isGranted($attribute, $rootStorageRelativePath);
    }
}
