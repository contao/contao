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

use ApiPlatform\Metadata\Get;

#[Get(
    uriTemplate: '/versioned-example',
    outputFormats: ['json' => ['application/json']],
    provider: VersionedExampleProvider::class,
    security: "is_granted('ROLE_USER')",
)]
final readonly class VersionedExample
{
    public function __construct(public string $name)
    {
    }
}
