<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

class PermissionCheckingVirtualFilesystem implements VirtualFilesystemInterface
{
    use VirtualFilesystemDecoratorTrait;

    public function __construct(
        VirtualFilesystemInterface $virtualFilesystem,
        private readonly Security $security,
    ) {
        $this->inner = $virtualFilesystem;
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

    private function denyAccessUnlessGranted(string $attribute, Uuid|string $location): void
    {
        if ($this->security->isGranted($attribute)) {
            return;
        }

        $permission = array_flip((new \ReflectionClass(ContaoCorePermissions::class))->getConstants())[$attribute];
        $action = strtolower(str_replace('_', ' ', substr($permission, 9)));

        $exception = new AccessDeniedException(sprintf('Access denied to %s at location "%s".', $action, $location));
        $exception->setAttributes($attribute);
        $exception->setSubject($location);

        throw $exception;
    }
}
