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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Contao\ApiBundle\Version\ApiVersion;

/**
 * @implements ProviderInterface<VersionedExample>
 */
final class VersionedExampleProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): VersionedExample
    {
        return new VersionedExample($context[ApiVersion::CONTEXT_KEY]->isBefore('2026-03-01') ? 'Legacy example' : 'Modern example');
    }
}
