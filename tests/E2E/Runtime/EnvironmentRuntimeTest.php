<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Runtime;

use Contao\E2eTesting\ManagedEdition\ManagedEditionConfig;

class EnvironmentRuntimeTest extends RuntimeTest
{
    protected static function runtimeOptions(): array
    {
        return ['marker' => 'environment', 'env' => 'prod', 'debug' => false];
    }

    protected static function createApplicationConfig(): ManagedEditionConfig
    {
        return parent::createApplicationConfig()->withEnvironmentVariables([
            'APP_RUNTIME' => false,
            'APP_RUNTIME_OPTIONS' => json_encode([
                'runtime_marker' => 'environment',
                'env' => 'prod',
                'debug' => false,
            ], JSON_THROW_ON_ERROR),
        ]);
    }
}
