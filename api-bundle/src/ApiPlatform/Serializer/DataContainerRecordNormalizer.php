<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\ApiPlatform\Serializer;

use ApiPlatform\Metadata\Operation;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class DataContainerRecordNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param array{operation?: Operation, contao_table?: string} $context
     */
    public function normalize(mixed $data, string|null $format = null, array $context = []): array
    {
        \assert($data instanceof DataContainerRecord);

        return $data->toArray();
    }

    /**
     * @param array{operation?: Operation, contao_table?: string} $context
     */
    public function supportsNormalization(mixed $data, string|null $format = null, array $context = []): bool
    {
        return $data instanceof DataContainerRecord;
    }

    public function getSupportedTypes(string|null $format): array
    {
        return [
            DataContainerRecord::class => true,
        ];
    }

    /**
     * @param array{operation?: Operation, contao_table?: string} $context
     */
    public function denormalize(mixed $data, string $type, string|null $format = null, array $context = []): mixed
    {
        if (!is_a($type, DataContainerRecord::class, true)) {
            throw new LogicException(\sprintf('The "%s" denormalizer only supports "%s".', self::class, DataContainerRecord::class));
        }

        $data = $this->toArray($data);
        $table = $this->getTable($context);
        $id = $data['id'] ?? null;

        if (\array_key_exists('id', $data)) {
            unset($data['id']);
        }

        return DataContainerRecord::fromArray($table, $data, $id);
    }

    /**
     * @param array{operation?: Operation, contao_table?: string} $context
     */
    public function supportsDenormalization(mixed $data, string $type, string|null $format = null, array $context = []): bool
    {
        return is_a($type, DataContainerRecord::class, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(mixed $data): array
    {
        if ($data instanceof \ArrayObject) {
            return $data->getArrayCopy();
        }

        if (\is_array($data)) {
            return $data;
        }

        if ($data instanceof \Traversable) {
            return iterator_to_array($data);
        }

        throw new LogicException(\sprintf('Cannot denormalize "%s" from "%s".', DataContainerRecord::class, get_debug_type($data)));
    }

    /**
     * @param array{operation?: Operation, contao_table?: string} $context
     */
    private function getTable(array $context): string
    {
        $table = $context['contao_table'] ?? null;

        if (!\is_string($table) || '' === $table) {
            $operation = $context['operation'] ?? null;

            if ($operation instanceof Operation) {
                $table = $operation->getExtraProperties()['contao']['table'] ?? null;
            }
        }

        if (!\is_string($table) || '' === $table) {
            throw new LogicException(\sprintf('Cannot denormalize "%s" without a Contao table in the serializer context.', DataContainerRecord::class));
        }

        return $table;
    }
}
