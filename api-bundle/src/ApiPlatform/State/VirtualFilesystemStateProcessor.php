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
use Contao\CoreBundle\File\UploadSizeProvider;
use Contao\CoreBundle\Filesystem\Dbafs\UnableToResolveUuidException;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\CoreBundle\Filesystem\MaximumStreamSizeExceededException;
use Contao\CoreBundle\Filesystem\PermissionCheckingVirtualFilesystem;
use Contao\CoreBundle\Filesystem\SizeLimitingVirtualFilesystemWriter;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<mixed, VirtualFilesystemItem>
 */
final class VirtualFilesystemStateProcessor implements ProcessorInterface
{
    private readonly VirtualFilesystemInterface $filesStorage;
    private readonly SizeLimitingVirtualFilesystemWriter $filesWriter;

    public function __construct(
        VirtualFilesystem $filesStorage,
        Security $security,
        private readonly RequestStack $requestStack,
        private readonly SchemaAwareObjectNormalizer $objectNormalizer,
        private readonly VirtualFilesystemItemFactory $itemFactory,
        private readonly UploadSizeProvider $uploadSizeProvider,
    ) {
        $this->filesStorage = new PermissionCheckingVirtualFilesystem($filesStorage, $security);
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

        $this->filesStorage->move($source, $destination);

        return $this->getItem($destination);
    }

    private function upload(Uuid|string $location, mixed $request): VirtualFilesystemItem
    {
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('An upload body is required.');
        }

        $maximumUploadSize = $this->uploadSizeProvider->getMaximumUploadSize();

        try {
            $this->filesWriter->writeStream(
                $location,
                $request->getContent(true),
                $maximumUploadSize,
            );
        } catch (MaximumStreamSizeExceededException $exception) {
            throw new HttpException(413, \sprintf('The upload exceeds the maximum size of %d bytes.', $maximumUploadSize), $exception);
        }

        return $this->getItem($location);
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
