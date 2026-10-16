<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Contao\ApiBundle\Dto\VirtualFilesystemItem;
use Contao\ApiBundle\Dto\VirtualFilesystemItemFactory;
use Contao\ApiBundle\Dto\VirtualFilesystemMove;
use Contao\ApiBundle\Serializer\SchemaAwareObjectNormalizer;
use Contao\CoreBundle\File\ImageTooLargeException;
use Contao\CoreBundle\File\InvalidImageException;
use Contao\CoreBundle\File\UploadSizeProvider;
use Contao\CoreBundle\File\UploadValidator;
use Contao\CoreBundle\Filesystem\Dbafs\UnableToResolveUuidException;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\CoreBundle\Filesystem\MaximumStreamSizeExceededException;
use Contao\CoreBundle\Filesystem\PermissionCheckingVirtualFilesystem;
use Contao\CoreBundle\Filesystem\SizeLimitingVirtualFilesystemWriter;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<mixed, VirtualFilesystemItem>
 */
final class VirtualFilesystemStateProcessor implements ProcessorInterface
{
    private readonly VirtualFilesystemInterface $filesStorage;
    private readonly SizeLimitingVirtualFilesystemWriter $filesWriter;
    private readonly VirtualFilesystem $filesStorageForCleanup;

    public function __construct(
        VirtualFilesystem $filesStorage,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly SchemaAwareObjectNormalizer $objectNormalizer,
        private readonly VirtualFilesystemItemFactory $itemFactory,
        private readonly UploadSizeProvider $uploadSizeProvider,
        private readonly UploadValidator $uploadValidator,
    ) {
        $this->filesStorage = new PermissionCheckingVirtualFilesystem($filesStorage, $security);
        $this->filesStorageForCleanup = $filesStorage;
        $this->filesWriter = new SizeLimitingVirtualFilesystemWriter($this->filesStorage);
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): VirtualFilesystemItem
    {
        try {
            return $this->doProcess($data, $operation, $uriVariables, $context);
        } catch (UnableToResolveUuidException $exception) {
            throw new NotFoundHttpException('The requested file or directory does not exist.', $exception);
        }
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    private function doProcess(mixed $data, Operation $operation, array $uriVariables, array $context): VirtualFilesystemItem
    {
        if ($data instanceof VirtualFilesystemMove) {
            return $this->move($data);
        }

        $request = $context['request'] ?? $this->requestStack->getCurrentRequest();

        if ('metadata' === ($operation->getExtraProperties()['contao']['operation'] ?? null)) {
            return $this->updateMetadata($request);
        }

        $path = $uriVariables['pathOrUuid'] ?? $uriVariables['path'] ?? null;

        if (!\is_string($path) || '' === $path) {
            throw new BadRequestHttpException('A file path or UUID is required.');
        }

        return $this->upload($this->toLocation($path), $request);
    }

    private function updateMetadata(mixed $request): VirtualFilesystemItem
    {
        [$location, $data] = $this->parseMetadataRequest($request);

        try {
            $update = $this->objectNormalizer->fromArray(ExtraMetadata::class, $data);
        } catch (\InvalidArgumentException|NotNormalizableValueException|\TypeError|\ValueError $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        if (!$update instanceof ExtraMetadata) {
            throw new \LogicException(\sprintf('Expected an instance of "%s".', ExtraMetadata::class));
        }

        $item = $this->filesStorage->get($location);

        if (!$item || !$item->isFile()) {
            throw new NotFoundHttpException('The requested file does not exist.');
        }

        $metadata = $item->getExtraMetadata();

        foreach ($update->all() as $key => $value) {
            $metadata->set($key, $value);
        }

        $this->filesStorage->setExtraMetadata($location, $metadata);

        return $this->getItem($location);
    }

    /**
     * @return array{Uuid|string, array<string, mixed>}
     */
    private function parseMetadataRequest(mixed $request): array
    {
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('A metadata body is required.');
        }

        try {
            $object = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        if (!$object instanceof \stdClass || !\is_array($payload) || array_diff_key($payload, ['path' => true, 'data' => true])) {
            throw new BadRequestHttpException('The metadata body must contain only a non-empty path or UUID and a data object.');
        }

        $path = $payload['path'] ?? null;
        $data = $payload['data'] ?? null;

        if (!\is_string($path) || '' === $path || !(($object->data ?? null) instanceof \stdClass) || !\is_array($data)) {
            throw new BadRequestHttpException('The metadata body must contain only a non-empty path or UUID and a data object.');
        }

        return [$this->toLocation($path), $data];
    }

    private function move(VirtualFilesystemMove $move): VirtualFilesystemItem
    {
        if ('' === $move->source || '' === $move->destination) {
            throw new BadRequestHttpException('Source and destination paths or UUIDs are required.');
        }

        $source = $this->toLocation($move->source);
        $destination = $this->toLocation($move->destination);
        $destination = $destination instanceof Uuid ? $this->filesStorage->resolveUuid($destination) : $destination;

        $source = $source instanceof Uuid ? $this->filesStorage->resolveUuid($source) : $source;
        $this->validatePath($source);
        $this->validatePath($destination);
        $item = $this->filesStorage->get($source);

        if (!$item) {
            throw new NotFoundHttpException('The requested file or directory does not exist.');
        }

        if ($item->isFile()) {
            $this->validateUploadFilename($destination);

            if (Path::getExtension($source) !== Path::getExtension($destination)) {
                throw new BadRequestHttpException('Changing the file extension is not allowed.');
            }
        }

        $this->filesStorage->move($source, $destination);

        return $this->getItem($destination);
    }

    private function upload(Uuid|string $location, mixed $request): VirtualFilesystemItem
    {
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('An upload body is required.');
        }

        $location = $location instanceof Uuid ? $this->filesStorage->resolveUuid($location) : $location;
        $this->validatePath($location);
        $this->validateUploadFilename($location);

        // Authorize before buffering or parsing content; the writer also checks permissions.
        if (!$this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_SUBPATH, Path::join($this->filesStorageForCleanup->getPrefix(), $location)) || !$this->security->isGranted(ContaoCorePermissions::USER_CAN_UPLOAD_FILES)) {
            throw new AccessDeniedException('Access denied to upload files at this location.');
        }

        $maximumUploadSize = $this->uploadSizeProvider->getMaximumUploadSize();
        $contents = $request->getContent(true);
        $sanitizedStream = null;

        try {
            if (\in_array(Path::getExtension($location, true), ['svg', 'svgz'], true)) {
                $svg = stream_get_contents($contents, $maximumUploadSize);

                if (false === $svg) {
                    throw new \RuntimeException('Could not read the upload body.');
                }

                $overflow = fread($contents, 1);

                if (false === $overflow) {
                    throw new \RuntimeException('Could not read the upload body.');
                }

                if ('' !== $overflow) {
                    throw new MaximumStreamSizeExceededException($maximumUploadSize);
                }

                $svg = $this->uploadValidator->sanitizeSvg($svg);

                if (null === $svg) {
                    throw new BadRequestHttpException('Invalid SVG.');
                }

                $sanitizedStream = fopen('php://temp', 'w+');

                if (false === $sanitizedStream || \strlen($svg) !== fwrite($sanitizedStream, $svg)) {
                    throw new \RuntimeException('Could not buffer the sanitized SVG.');
                }

                rewind($sanitizedStream);
            }

            $this->filesWriter->writeStream($location, $sanitizedStream ?? $contents, $maximumUploadSize);
        } catch (MaximumStreamSizeExceededException $exception) {
            throw new HttpException(413, \sprintf('The upload exceeds the maximum size of %d bytes.', $maximumUploadSize), $exception);
        } finally {
            if (\is_resource($sanitizedStream)) {
                fclose($sanitizedStream);
            }

            if (\is_resource($contents)) {
                fclose($contents);
            }
        }

        if ($this->uploadValidator->requiresImageValidation($location)) {
            try {
                $this->validateStoredImage($location);
            } catch (ImageTooLargeException|InvalidImageException $exception) {
                // Rejection is part of the authorized upload, not a separate user delete operation.
                $this->filesStorageForCleanup->delete($location);

                throw new BadRequestHttpException($exception->getMessage(), $exception);
            }
        }

        return $this->getItem($location);
    }

    private function validatePath(string $path): void
    {
        foreach (explode('/', $path) as $component) {
            if (!$this->uploadValidator->isValidFilename($component)) {
                throw new BadRequestHttpException('Invalid file or directory name.');
            }
        }
    }

    private function validateUploadFilename(string $path): void
    {
        if (!$this->uploadValidator->isAllowedFilename($path)) {
            throw new BadRequestHttpException('The file extension is not allowed for uploads.');
        }
    }

    private function validateStoredImage(string $location): void
    {
        $contents = $this->filesStorage->readStream($location);
        $temporary = null;

        try {
            $metadata = stream_get_meta_data($contents);

            if ('STDIO' === $metadata['stream_type'] && 'plainfile' === $metadata['wrapper_type'] && Path::isAbsolute($metadata['uri'] ?? '')) {
                $this->uploadValidator->validateImage($metadata['uri']);

                return;
            }

            $temporary = tmpfile();

            if (false === $temporary) {
                throw new \RuntimeException('Could not create a temporary image file.');
            }

            if (false === stream_copy_to_stream($contents, $temporary)) {
                throw new \RuntimeException('Could not read the uploaded image.');
            }

            $this->uploadValidator->validateImage(stream_get_meta_data($temporary)['uri']);
        } finally {
            fclose($contents);

            if (\is_resource($temporary)) {
                fclose($temporary);
            }
        }
    }

    private function getItem(Uuid|string $location): VirtualFilesystemItem
    {
        $item = $this->filesStorage->get($location);

        if (!$item) {
            throw new \LogicException(\sprintf('The filesystem item "%s" was not found after writing it.', $location));
        }

        return $this->itemFactory->create($item);
    }

    private function toLocation(string $location): Uuid|string
    {
        return Uuid::isValid($location) ? Uuid::fromString($location) : $location;
    }
}
