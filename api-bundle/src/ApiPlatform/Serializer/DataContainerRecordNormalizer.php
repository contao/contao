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

use ApiPlatform\JsonLd\AnonymousContextBuilderInterface;
use ApiPlatform\Metadata\Operation;
use Contao\ApiBundle\DataContainer\DataContainerRelationReference;
use Contao\ApiBundle\DataContainer\DataContainerRelationResolver;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class DataContainerRecordNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private readonly DataContainerRelationResolver $relationResolver,
        private readonly AnonymousContextBuilderInterface $contextBuilder,
    ) {
    }

    /**
     * @param array{operation?: Operation, contao_table?: string} $context
     */
    public function normalize(mixed $data, string|null $format = null, array $context = []): array
    {
        \assert($data instanceof DataContainerRecord);
        $normalized = $this->normalizeRelationReferences($data->toArray(), $format);
        $iri = $this->relationResolver->resolveRecordToIri($data);

        if (null !== $data->id && null !== $iri) {
            $normalized['id'] = $this->normalizeRelationReferences(new DataContainerRelationReference($data->id, $iri), $format);
        }

        if ('jsonld' !== $format || null === $iri) {
            return $normalized;
        }

        $jsonLdContext = ['operation' => $context['operation'] ?? null, 'iri' => $iri];

        if (isset($context['jsonld_has_context'])) {
            $jsonLdContext['has_context'] = true;
        }

        return $this->contextBuilder->getAnonymousResourceContext($data, $jsonLdContext) + $normalized;
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
     * @param array{operation?: Operation, contao_table?: string, object_to_populate?: DataContainerRecord} $context
     */
    public function denormalize(mixed $data, string $type, string|null $format = null, array $context = []): DataContainerRecord
    {
        if (!is_a($type, DataContainerRecord::class, true)) {
            throw new LogicException(\sprintf('The "%s" denormalizer only supports "%s".', self::class, DataContainerRecord::class));
        }

        $data = $this->toArray($data);
        unset($data['@context'], $data['@id'], $data['@type']);
        $data = $this->normalizeInputReferences($data);
        $table = $this->getTable($context);
        $id = $this->getRecordIdentifier($data['id'] ?? null);
        $record = $context[AbstractNormalizer::OBJECT_TO_POPULATE] ?? null;

        if ($record instanceof DataContainerRecord) {
            if ($table !== $record->table || (\array_key_exists('id', $data) && ((!\is_int($id) && !\is_string($id)) || (string) $id !== (string) $record->id))) {
                throw new NotNormalizableValueException('Cannot change the table or identifier of an existing record.');
            }

            unset($data['id']);

            // Keep omitted fields out of validation and form submission
            $record->data = $data;

            return $record;
        }

        if (\array_key_exists('id', $data)) {
            unset($data['id']);
        }

        return DataContainerRecord::fromArray($table, $data, $id);
    }

    /**
     * @param array{
     *     operation?: Operation,
     *     contao_table?: string,
     *     input?: array{class?: class-string|null},
     * } $context
     */
    public function supportsDenormalization(mixed $data, string $type, string|null $format = null, array $context = []): bool
    {
        if (!is_a($type, DataContainerRecord::class, true)) {
            return false;
        }

        $inputClass = $context['input']['class'] ?? null;

        return null === $inputClass || is_a($inputClass, DataContainerRecord::class, true);
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

    private function normalizeRelationReferences(mixed $value, string|null $format): mixed
    {
        if ($value instanceof DataContainerRelationReference) {
            return 'jsonld' === $format
                ? ['@id' => $value->iri, 'id' => $value->id]
                : ['id' => $value->id, 'iri' => $value->iri];
        }

        if (!\is_array($value)) {
            return $value;
        }

        return array_map(fn (mixed $item): mixed => $this->normalizeRelationReferences($item, $format), $value);
    }

    private function normalizeInputReferences(mixed $value): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }

        if (\array_key_exists('@id', $value)) {
            $value['iri'] = $value['@id'];
            unset($value['@id']);
        }

        return array_map($this->normalizeInputReferences(...), $value);
    }

    private function getRecordIdentifier(mixed $value): mixed
    {
        return \is_array($value) ? ($value['id'] ?? null) : $value;
    }

    /**
     * @param array{operation?: Operation, contao_table?: string, object_to_populate?: DataContainerRecord} $context
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
