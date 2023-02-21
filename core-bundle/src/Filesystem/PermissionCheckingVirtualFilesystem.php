<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Filesystem\PublicUri\OptionsInterface;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Psr\Http\Message\UriInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

class PermissionCheckingVirtualFilesystem implements VirtualFilesystemInterface
{
    public function __construct(
        private readonly VirtualFilesystemInterface $virtualFilesystem,
        private readonly Security $security,
    ) {
    }

    public function has(Uuid|string $location, int $accessFlags = self::NONE): bool
    {
        return $this->virtualFilesystem->has($location, $accessFlags);
    }

    public function fileExists(Uuid|string $location, int $accessFlags = self::NONE): bool
    {
        return $this->virtualFilesystem->fileExists($location, $accessFlags);
    }

    public function directoryExists(Uuid|string $location, int $accessFlags = self::NONE): bool
    {
        return $this->virtualFilesystem->directoryExists($location, $accessFlags);
    }

    public function read(Uuid|string $location): string
    {
        return $this->virtualFilesystem->read($location);
    }

    public function readStream(Uuid|string $location)
    {
        return $this->virtualFilesystem->readStream($location);
    }

    public function write(Uuid|string $location, string $contents, array $options = []): void
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_UPLOAD_FILES, $location);

        $this->virtualFilesystem->write($location, $contents, $options);
    }

    public function writeStream(Uuid|string $location, $contents, array $options = []): void
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_UPLOAD_FILES, $location);

        $this->virtualFilesystem->writeStream($location, $contents, $options);
    }

    public function delete(Uuid|string $location): void
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_DELETE_FILE, $location);

        $this->virtualFilesystem->delete($location);
    }

    public function deleteDirectory(Uuid|string $location): void
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_DELETE_RECURSIVELY, $location);

        $this->virtualFilesystem->deleteDirectory($location);
    }

    public function createDirectory(Uuid|string $location, array $options = []): void
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_UPLOAD_FILES, $location);

        $this->virtualFilesystem->createDirectory($location, $options);
    }

    public function copy(Uuid|string $source, string $destination, array $options = []): void
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_UPLOAD_FILES, $destination);

        $this->virtualFilesystem->copy($source, $destination, $options);
    }

    public function move(Uuid|string $source, string $destination, array $options = []): void
    {
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_DELETE_FILE, $source);
        $this->denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_UPLOAD_FILES, $destination);

        $this->virtualFilesystem->move($source, $destination, $options);
    }

    public function get(Uuid|string $location, int $accessFlags = self::NONE): FilesystemItem|null
    {
        return $this->virtualFilesystem->get($location, $accessFlags);
    }

    public function listContents(Uuid|string $location, bool $deep = false, int $accessFlags = self::NONE): FilesystemItemIterator
    {
        return $this->virtualFilesystem->listContents($location, $deep, $accessFlags);
    }

    public function getLastModified(Uuid|string $location, int $accessFlags = self::NONE): int
    {
        return $this->virtualFilesystem->getLastModified($location, $accessFlags);
    }

    public function getFileSize(Uuid|string $location, int $accessFlags = self::NONE): int
    {
        return $this->virtualFilesystem->getFileSize($location, $accessFlags);
    }

    public function getMimeType(Uuid|string $location, int $accessFlags = self::NONE): string
    {
        return $this->virtualFilesystem->getMimeType($location, $accessFlags);
    }

    public function getExtraMetadata(Uuid|string $location, int $accessFlags = self::NONE): array
    {
        return $this->virtualFilesystem->getExtraMetadata($location, $accessFlags);
    }

    public function setExtraMetadata(Uuid|string $location, array $metadata): void
    {
        $this->virtualFilesystem->setExtraMetadata($location, $metadata);
    }

    public function generatePublicUri(Uuid|string $location, OptionsInterface $options = null): UriInterface|null
    {
        return $this->virtualFilesystem->generatePublicUri($location, $options);
    }

    private function denyAccessUnlessGranted(string $attribute, Uuid|string $location): void
    {
        if ($this->security->isGranted($attribute)) {
            return;
        }

        $permission = array_flip((new \ReflectionClass(ContaoCorePermissions::class))->getConstants())[$attribute];
        $message = sprintf(
            'Access denied to %s at location "%s".',
            strtolower(str_replace('_', ' ', substr($permission, 9))),
            $location
        );

        $exception = new AccessDeniedException($message);
        $exception->setAttributes($attribute);
        $exception->setSubject($location);

        throw $exception;
    }
}
