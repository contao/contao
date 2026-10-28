<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Serializer;

use Contao\ApiBundle\Relation\RelationReference;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class RelationReferenceNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data, string|null $format = null, array $context = []): array
    {
        \assert($data instanceof RelationReference);

        return 'jsonld' === $format
            ? ['@id' => $data->iri, 'id' => $data->id]
            : ['id' => $data->id, 'iri' => $data->iri];
    }

    public function supportsNormalization(mixed $data, string|null $format = null, array $context = []): bool
    {
        return $data instanceof RelationReference;
    }

    public function getSupportedTypes(string|null $format): array
    {
        return [RelationReference::class => true];
    }
}
