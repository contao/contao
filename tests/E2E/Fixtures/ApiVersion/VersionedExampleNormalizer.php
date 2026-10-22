<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace App\Api;

use Contao\ApiBundle\Version\ApiVersion;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class VersionedExampleNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data, string|null $format = null, array $context = []): array
    {
        \assert($data instanceof VersionedExample);
        $property = $context[ApiVersion::CONTEXT_KEY]->isBefore('2026-03-01') ? 'legacyName' : 'displayName';

        return [$property => $data->name];
    }

    public function supportsNormalization(mixed $data, string|null $format = null, array $context = []): bool
    {
        return $data instanceof VersionedExample;
    }

    public function getSupportedTypes(string|null $format): array
    {
        return [VersionedExample::class => true];
    }
}
