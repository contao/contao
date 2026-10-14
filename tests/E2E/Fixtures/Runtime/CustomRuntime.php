<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace App\Runtime;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Runtime\SymfonyRuntime;

class CustomRuntime extends SymfonyRuntime
{
    public function __construct(array $options = [])
    {
        $_SERVER['HTTPS'] = 'on';
        $_GET['runtime_marker'] = $options['runtime_marker'] ?? '';
        $_SERVER['HTTP_X_RUNTIME_DEBUG'] = $options['debug'] ?? false ? '1' : '0';

        parent::__construct($options);
    }

    protected function resolveType(string $type): mixed
    {
        if (InputInterface::class === $type && 'runtime:environment' === ($_SERVER['argv'][1] ?? null)) {
            $_SERVER['APP_ENV'] = 'dev';

            return new ArgvInput(['console', 'debug:container', '--parameter=kernel.environment', '--env=prod']);
        }

        return parent::resolveType($type);
    }
}
