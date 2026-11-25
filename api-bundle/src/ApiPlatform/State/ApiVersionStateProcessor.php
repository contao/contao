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
use Contao\ApiBundle\Version\ApiVersion;
use Contao\ApiBundle\Version\ApiVersionProvider;

/**
 * @implements ProcessorInterface<mixed, mixed>
 */
final readonly class ApiVersionStateProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<mixed, mixed> $decorated
     */
    public function __construct(
        private ProcessorInterface $decorated,
        private ApiVersionProvider $versionProvider,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $context[ApiVersion::CONTEXT_KEY] ??= $this->versionProvider->getVersion($context['request'] ?? null);

        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}
