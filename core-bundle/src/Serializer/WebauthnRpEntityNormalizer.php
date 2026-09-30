<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Serializer;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webauthn\Denormalizer\PublicKeyCredentialRpEntityDenormalizer;
use Webauthn\PublicKeyCredentialRpEntity;

final class WebauthnRpEntityNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly PublicKeyCredentialRpEntityDenormalizer $rpEntityNormalizer,
    ) {
    }

    public function normalize(mixed $data, string|null $format = null, array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $rpId = $request?->getHost();

        return $this->rpEntityNormalizer->normalize(PublicKeyCredentialRpEntity::create('', $rpId), $format, $context) ?? [];
    }

    public function supportsNormalization(mixed $data, string|null $format = null, array $context = []): bool
    {
        return $data instanceof PublicKeyCredentialRpEntity
            && null === $data->id
            && $this->requestStack->getCurrentRequest();
    }

    public function getSupportedTypes(string|null $format): array
    {
        return [PublicKeyCredentialRpEntity::class => false];
    }
}
